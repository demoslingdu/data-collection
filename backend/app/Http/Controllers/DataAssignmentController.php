<?php

namespace App\Http\Controllers;

use App\Models\DataRecord;
use App\Models\DataRecordAssignment;
use App\Models\Company;
use App\Models\User;
use App\Services\DataAssignmentService;
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
    protected DataAssignmentService $assignmentService;

    public function __construct(DataAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }
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

        // 移除状态过滤（已删除status字段）

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
            'assigned_to' => 'required|exists:users,id',
        ]);

        $user = Auth::user();
        
        try {
            $assignments = $this->assignmentService->createAssignment(
                $validated['data_record_ids'],
                $validated['company_id'],
                $validated['assigned_to']
            );

            return response()->json([
                'success' => true,
                'message' => '数据分发创建成功',
                'data' => $assignments,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '数据分发创建失败：' . $e->getMessage(),
            ], 400);
        }
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
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        // 权限检查
        $user = Auth::user();
        if ($user->role !== 'admin' && $assignment->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => '无权修改该分发记录',
            ], 403);
        }

        // 更新分配信息
        $assignment->update($validated);

        return response()->json([
            'success' => true,
            'message' => '分发记录更新成功',
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
     * 批量分发数据到多个公司
     */
    public function batchAssign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data_record_ids' => 'required|array',
            'data_record_ids.*' => 'exists:data_records,id',
            'company_ids' => 'required|array',
            'company_ids.*' => 'exists:companies,id',
        ]);

        $user = Auth::user();
        
        try {
            $assignments = $this->assignmentService->batchAssign(
                $validated['data_record_ids'],
                $validated['company_ids']
            );

            return response()->json([
                'success' => true,
                'message' => '批量分发创建成功',
                'data' => $assignments,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '批量分发创建失败：' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 批量分发数据到多个用户
     */
    public function batchAssignToUsers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data_record_ids' => 'required|array',
            'data_record_ids.*' => 'exists:data_records,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $user = Auth::user();
        
        try {
            $assignments = $this->assignmentService->batchAssignToUsers(
                $validated['data_record_ids'],
                $validated['user_ids']
            );

            return response()->json([
                'success' => true,
                'message' => '批量分发到用户创建成功',
                'data' => $assignments,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '批量分发到用户创建失败：' . $e->getMessage(),
            ], 400);
        }
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
     * 获取可用的数据记录
     */
    public function availableRecords(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = DataRecord::query();

        // 如果指定了公司，则排除已分发给该公司的记录
        if (isset($validated['company_id'])) {
            $assignedIds = DataRecordAssignment::where('company_id', $validated['company_id'])
                ->pluck('data_record_id')
                ->toArray();

            if (!empty($assignedIds)) {
                $query->whereNotIn('id', $assignedIds);
            }
        }

        $records = $query->with(['company', 'category'])
            ->orderBy('created_at', 'desc')
            ->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'success' => true,
            'data' => $records,
        ]);
    }

    /**
     * 获取用户可领取的数据列表
     */
    public function getClaimableAssignments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $user = Auth::user();
        
        try {
            $assignments = $this->assignmentService->getClaimableAssignments(
                $user->id,
                $validated['per_page'] ?? 15
            );

            return response()->json([
                'success' => true,
                'data' => $assignments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取可领取数据失败：' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 用户领取数据
     */
    public function claimAssignment(Request $request, int $assignmentId): JsonResponse
    {
        $user = Auth::user();
        
        try {
            $assignment = $this->assignmentService->claimAssignment($assignmentId, $user->id);

            return response()->json([
                'success' => true,
                'message' => '数据领取成功',
                'data' => $assignment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '数据领取失败：' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 用户完成数据处理
     */
    public function completeAssignment(Request $request, int $assignmentId): JsonResponse
    {
        $user = Auth::user();
        
        try {
            $assignment = $this->assignmentService->completeAssignment(
                $assignmentId, 
                $user->id
            );

            return response()->json([
                'success' => true,
                'message' => '数据处理完成',
                'data' => $assignment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '完成数据处理失败：' . $e->getMessage(),
            ], 400);
        }
    }
}
