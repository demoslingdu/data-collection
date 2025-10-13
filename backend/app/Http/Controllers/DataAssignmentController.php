<?php

namespace App\Http\Controllers;

use App\Models\DataRecord;
use App\Models\DataRecordAssignment;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * 数据分发管理控制器
 * 处理数据记录分发给公司的操作
 */
class DataAssignmentController extends Controller
{
    /**
     * 获取数据分发列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = DataRecordAssignment::with(['dataRecord', 'company', 'assignedBy', 'assignedTo']);

        // 根据用户角色过滤数据
        $user = Auth::user();
        if ($user->role !== 'admin') {
            $query->byCompany($user->company_id);
        }

        // 状态过滤
        if ($request->filled('status')) {
            $query->byStatus($request->get('status'));
        }

        // 公司过滤
        if ($request->filled('company_id')) {
            $query->byCompany($request->get('company_id'));
        }

        // 分配给指定用户的过滤
        if ($request->filled('assigned_to')) {
            $query->byAssignedTo($request->get('assigned_to'));
        }

        // 日期范围过滤
        if ($request->filled('date_from')) {
            $query->where('assigned_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('assigned_at', '<=', $request->get('date_to'));
        }

        // 分页
        $perPage = $request->get('per_page', 15);
        $assignments = $query->orderBy('assigned_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $assignments,
        ]);
    }

    /**
     * 创建数据分发
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data_record_ids' => 'required|array',
            'data_record_ids.*' => 'exists:data_records,id',
            'company_id' => 'required|exists:companies,id',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        $assignments = [];

        DB::transaction(function () use ($validated, $user, &$assignments) {
            foreach ($validated['data_record_ids'] as $dataRecordId) {
                // 检查是否已经分发给该公司
                $existingAssignment = DataRecordAssignment::where('data_record_id', $dataRecordId)
                    ->where('company_id', $validated['company_id'])
                    ->first();

                if ($existingAssignment) {
                    continue; // 跳过已分发的记录
                }

                $assignment = DataRecordAssignment::create([
                    'data_record_id' => $dataRecordId,
                    'company_id' => $validated['company_id'],
                    'assigned_by' => $user->id,
                    'assigned_to' => $validated['assigned_to'] ?? null,
                    'assigned_at' => now(),
                    'status' => 'pending',
                    'notes' => $validated['notes'] ?? null,
                ]);

                $assignments[] = $assignment;
            }
        });

        return response()->json([
            'success' => true,
            'message' => '数据分发创建成功',
            'data' => $assignments,
        ], 201);
    }

    /**
     * 获取指定分发详情
     */
    public function show(DataRecordAssignment $assignment): JsonResponse
    {
        $assignment->load(['dataRecord', 'company', 'assignedBy', 'assignedTo']);

        // 权限检查
        $user = Auth::user();
        if ($user->role !== 'admin' && $assignment->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => '无权访问该分发记录',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $assignment,
        ]);
    }

    /**
     * 更新分发状态
     */
    public function update(Request $request, DataRecordAssignment $assignment): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        // 权限检查
        $user = Auth::user();
        if ($user->role !== 'admin' && $assignment->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => '无权修改该分发记录',
            ], 403);
        }

        // 更新状态时间戳
        $updateData = $validated;
        if ($validated['status'] === 'in_progress' && $assignment->status !== 'in_progress') {
            $updateData['started_at'] = now();
        } elseif ($validated['status'] === 'completed' && $assignment->status !== 'completed') {
            $updateData['completed_at'] = now();
        }

        $assignment->update($updateData);

        return response()->json([
            'success' => true,
            'message' => '分发状态更新成功',
            'data' => $assignment,
        ]);
    }

    /**
     * 删除分发记录
     */
    public function destroy(DataRecordAssignment $assignment): JsonResponse
    {
        // 权限检查
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => '只有管理员可以删除分发记录',
            ], 403);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => '分发记录删除成功',
        ]);
    }

    /**
     * 批量分发数据
     */
    public function batchAssign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data_record_ids' => 'required|array',
            'data_record_ids.*' => 'exists:data_records,id',
            'company_ids' => 'required|array',
            'company_ids.*' => 'exists:companies,id',
            'notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        $assignments = [];

        DB::transaction(function () use ($validated, $user, &$assignments) {
            foreach ($validated['data_record_ids'] as $dataRecordId) {
                foreach ($validated['company_ids'] as $companyId) {
                    // 检查是否已经分发给该公司
                    $existingAssignment = DataRecordAssignment::where('data_record_id', $dataRecordId)
                        ->where('company_id', $companyId)
                        ->first();

                    if ($existingAssignment) {
                        continue; // 跳过已分发的记录
                    }

                    $assignment = DataRecordAssignment::create([
                        'data_record_id' => $dataRecordId,
                        'company_id' => $companyId,
                        'assigned_by' => $user->id,
                        'assigned_at' => now(),
                        'status' => 'pending',
                        'notes' => $validated['notes'] ?? null,
                    ]);

                    $assignments[] = $assignment;
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => '批量分发创建成功',
            'data' => $assignments,
        ], 201);
    }

    /**
     * 获取分发统计信息
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = DataRecordAssignment::query();

        // 根据用户角色过滤数据
        if ($user->role !== 'admin') {
            $query->byCompany($user->company_id);
        }

        // 日期范围过滤
        if ($request->filled('date_from')) {
            $query->where('assigned_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('assigned_at', '<=', $request->get('date_to'));
        }

        $statistics = [
            'total' => $query->count(),
            'pending' => $query->clone()->pending()->count(),
            'in_progress' => $query->clone()->inProgress()->count(),
            'completed' => $query->clone()->completed()->count(),
            'by_company' => $query->clone()
                ->select('company_id', DB::raw('count(*) as count'))
                ->with('company:id,name')
                ->groupBy('company_id')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * 获取可分发的数据记录
     */
    public function availableRecords(Request $request): JsonResponse
    {
        $query = DataRecord::query();

        // 搜索过滤
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 平台过滤
        if ($request->filled('platform')) {
            $query->where('platform', $request->get('platform'));
        }

        // 状态过滤
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // 分页
        $perPage = $request->get('per_page', 15);
        $records = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $records,
        ]);
    }
}
