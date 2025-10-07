<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\DataRecord;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

/**
 * 数据记录控制器
 * 处理数据记录的完整CRUD操作
 */
class DataRecordController extends Controller
{
    /**
     * 获取数据记录列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DataRecord::with('user');

            // 筛选条件
            if ($request->has('category') && $request->category) {
                $query->byCategory($request->category);
            }

            if ($request->has('status') && $request->status) {
                $query->byStatus($request->status);
            }

            // 搜索标题
            if ($request->has('search') && $request->search) {
                $query->where('title', 'like', '%' . $request->search . '%');
            }

            // 排序
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // 分页
            $perPage = $request->get('per_page', 15);
            $records = $query->paginate($perPage);

            return ApiResponse::paginated($records, '获取数据记录列表成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('获取数据记录列表失败', $e->getMessage());
        }
    }

    /**
     * 创建数据记录
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'status' => 'nullable|in:active,inactive',
                'metadata' => 'nullable|array',
            ], [
                'title.required' => '标题不能为空',
                'title.max' => '标题不能超过255个字符',
                'category.max' => '分类不能超过100个字符',
                'status.in' => '状态值无效',
                'metadata.array' => '元数据必须是数组格式',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $record = DataRecord::create([
                'title' => $request->title,
                'content' => $request->content,
                'category' => $request->category,
                'status' => $request->status ?? DataRecord::STATUS_ACTIVE,
                'metadata' => $request->metadata,
                'user_id' => Auth::id(),
            ]);

            $record->load('user');

            return ApiResponse::created($record, '创建数据记录成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('创建数据记录失败', $e->getMessage());
        }
    }

    /**
     * 获取单个数据记录详情
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $record = DataRecord::with('user')->find($id);

            if (!$record) {
                return ApiResponse::notFound('数据记录不存在');
            }

            return ApiResponse::success($record, '获取数据记录详情成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('获取数据记录详情失败', $e->getMessage());
        }
    }

    /**
     * 更新数据记录
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $record = DataRecord::find($id);

            if (!$record) {
                return ApiResponse::notFound('数据记录不存在');
            }

            // 检查权限：只有创建者可以编辑
            if ($record->user_id !== Auth::id()) {
                return ApiResponse::forbidden('您没有权限编辑此记录');
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'content' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'status' => 'nullable|in:active,inactive',
                'metadata' => 'nullable|array',
            ], [
                'title.required' => '标题不能为空',
                'title.max' => '标题不能超过255个字符',
                'category.max' => '分类不能超过100个字符',
                'status.in' => '状态值无效',
                'metadata.array' => '元数据必须是数组格式',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $updateData = [];
            if ($request->has('title')) {
                $updateData['title'] = $request->title;
            }
            if ($request->has('content')) {
                $updateData['content'] = $request->content;
            }
            if ($request->has('category')) {
                $updateData['category'] = $request->category;
            }
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            if ($request->has('metadata')) {
                $updateData['metadata'] = $request->metadata;
            }

            $record->update($updateData);
            $record->load('user');

            return ApiResponse::updated($record, '更新数据记录成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('更新数据记录失败', $e->getMessage());
        }
    }

    /**
     * 删除数据记录
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $record = DataRecord::find($id);

            if (!$record) {
                return ApiResponse::notFound('数据记录不存在');
            }

            // 检查权限：只有创建者可以删除
            if ($record->user_id !== Auth::id()) {
                return ApiResponse::forbidden('您没有权限删除此记录');
            }

            $record->delete();

            return ApiResponse::deleted('删除数据记录成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('删除数据记录失败', $e->getMessage());
        }
    }

    /**
     * 批量删除数据记录
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function batchDestroy(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:data_records,id',
            ], [
                'ids.required' => '请选择要删除的记录',
                'ids.array' => 'IDs必须是数组格式',
                'ids.min' => '至少选择一条记录',
                'ids.*.integer' => 'ID必须是整数',
                'ids.*.exists' => '选择的记录不存在',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $records = DataRecord::whereIn('id', $request->ids)
                ->where('user_id', Auth::id())
                ->get();

            if ($records->count() !== count($request->ids)) {
                return ApiResponse::forbidden('您只能删除自己创建的记录');
            }

            DataRecord::whereIn('id', $request->ids)->delete();

            return ApiResponse::deleted('批量删除数据记录成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('批量删除数据记录失败', $e->getMessage());
        }
    }

    /**
     * 获取数据统计信息
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $userId = Auth::id();

            $stats = [
                'total' => DataRecord::byUser($userId)->count(),
                'active' => DataRecord::byUser($userId)->byStatus(DataRecord::STATUS_ACTIVE)->count(),
                'inactive' => DataRecord::byUser($userId)->byStatus(DataRecord::STATUS_INACTIVE)->count(),
                'categories' => DataRecord::byUser($userId)
                    ->selectRaw('category, COUNT(*) as count')
                    ->whereNotNull('category')
                    ->groupBy('category')
                    ->pluck('count', 'category')
                    ->toArray(),
            ];

            return ApiResponse::success($stats, '获取统计信息成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('获取统计信息失败', $e->getMessage());
        }
    }
}
