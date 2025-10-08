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
            $query = DataRecord::with(['submitter', 'claimer']);

            // 按平台筛选
            if ($request->has('platform') && $request->platform) {
                $query->where('platform', $request->platform);
            }

            // 按领取状态筛选
            if ($request->has('is_claimed') && $request->has('is_claimed')) {
                $query->where('is_claimed', (bool)$request->is_claimed);
            }

            // 按完成状态筛选
            if ($request->has('is_completed') && $request->has('is_completed')) {
                $query->where('is_completed', (bool)$request->is_completed);
            }

            // 按重复状态筛选
            if ($request->has('is_duplicate') && $request->has('is_duplicate')) {
                $query->where('is_duplicate', (bool)$request->is_duplicate);
            }

            // 搜索平台ID
            if ($request->has('search') && $request->search) {
                $query->where('platform_id', 'like', '%' . $request->search . '%');
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
                'image_url' => 'required|url',
                'platform' => 'required|in:douyin,xiaohongshu,taobao,xianyu',
                'platform_id' => 'required|string|max:255',
            ], [
                'image_url.required' => '图片URL不能为空',
                'image_url.url' => '图片URL格式不正确',
                'platform.required' => '来源平台不能为空',
                'platform.in' => '来源平台必须是：douyin, xiaohongshu, taobao, xianyu',
                'platform_id.required' => '平台ID不能为空',
                'platform_id.max' => '平台ID不能超过255个字符',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            // 检查是否已存在相同平台和平台ID的记录
            $existingRecord = DataRecord::where('platform', $request->platform)
                ->where('platform_id', $request->platform_id)
                ->orderBy('created_at', 'desc')
                ->first();

            // 默认不标记为重复
            $isDuplicate = false;

            if ($existingRecord) {
                // 计算时间差（天数）
                $daysDiff = now()->diffInDays($existingRecord->created_at);
                
                // 如果在3天内（包括当天），标记为重复
                if ($daysDiff <= 3) {
                    $isDuplicate = true;
                }
                // 超过3天的算新客资，不标记重复（$isDuplicate 保持 false）
            }

            $record = DataRecord::create([
                'image_url' => $request->image_url,
                'submitter_id' => Auth::id(),
                'platform' => $request->platform,
                'platform_id' => $request->platform_id,
                'is_claimed' => false,
                'is_completed' => false,
                'is_duplicate' => $isDuplicate,
            ]);

            $record->load(['submitter', 'claimer']);

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
            $record = DataRecord::with(['submitter', 'claimer'])->find($id);

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

            // 检查权限：只有提交者可以编辑
            if ($record->submitter_id !== Auth::id()) {
                return ApiResponse::forbidden('您没有权限编辑此记录');
            }

            $validator = Validator::make($request->all(), [
                'image_url' => 'sometimes|required|url',
                'platform' => 'sometimes|required|in:douyin,xiaohongshu,taobao,xianyu',
                'platform_id' => 'sometimes|required|string|max:255',
                'is_duplicate' => 'sometimes|boolean',
            ], [
                'image_url.required' => '图片URL不能为空',
                'image_url.url' => '图片URL格式不正确',
                'platform.required' => '来源平台不能为空',
                'platform.in' => '来源平台必须是：douyin, xiaohongshu, taobao, xianyu',
                'platform_id.required' => '平台ID不能为空',
                'platform_id.max' => '平台ID不能超过255个字符',
                'is_duplicate.boolean' => '重复状态必须是布尔值',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            // 如果更新平台或平台ID，检查唯一性
            if ($request->has('platform') || $request->has('platform_id')) {
                $platform = $request->get('platform', $record->platform);
                $platformId = $request->get('platform_id', $record->platform_id);
                
                $existingRecord = DataRecord::where('platform', $platform)
                    ->where('platform_id', $platformId)
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingRecord) {
                    return ApiResponse::validationError(['platform_id' => ['该平台ID已存在']]);
                }
            }

            $updateData = [];
            if ($request->has('image_url')) {
                $updateData['image_url'] = $request->image_url;
            }
            if ($request->has('platform')) {
                $updateData['platform'] = $request->platform;
            }
            if ($request->has('platform_id')) {
                $updateData['platform_id'] = $request->platform_id;
            }
            if ($request->has('is_duplicate')) {
                $updateData['is_duplicate'] = $request->is_duplicate;
            }

            $record->update($updateData);
            $record->load(['submitter', 'claimer']);

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

            // 检查权限：只有提交者可以删除
            if ($record->submitter_id !== Auth::id()) {
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
                ->where('submitter_id', Auth::id())
                ->get();

            if ($records->count() !== count($request->ids)) {
                return ApiResponse::forbidden('您只能删除自己提交的记录');
            }

            DataRecord::whereIn('id', $request->ids)->delete();

            return ApiResponse::deleted('批量删除数据记录成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('批量删除数据记录失败', $e->getMessage());
        }
    }

    /**
     * 领取数据记录
     *
     * @param int $id
     * @return JsonResponse
     */
    public function claim(int $id): JsonResponse
    {
        try {
            $record = DataRecord::find($id);

            if (!$record) {
                return ApiResponse::notFound('数据记录不存在');
            }

            // 检查是否已被领取
            if ($record->is_claimed) {
                return ApiResponse::validationError(['record' => ['该记录已被领取']]);
            }

            // 检查是否是自己提交的记录
            if ($record->submitter_id === Auth::id()) {
                return ApiResponse::validationError(['record' => ['不能领取自己提交的记录']]);
            }

            $record->update([
                'is_claimed' => true,
                'claimer_id' => Auth::id(),
            ]);

            $record->load(['submitter', 'claimer']);

            return ApiResponse::updated($record, '领取数据记录成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('领取数据记录失败', $e->getMessage());
        }
    }

    /**
     * 完成数据记录
     *
     * @param int $id
     * @return JsonResponse
     */
    public function complete(int $id): JsonResponse
    {
        try {
            $record = DataRecord::find($id);

            if (!$record) {
                return ApiResponse::notFound('数据记录不存在');
            }

            // 检查权限：只有领取者可以标记完成
            if ($record->claimer_id !== Auth::id()) {
                return ApiResponse::forbidden('您没有权限完成此记录');
            }

            // 检查是否已被领取
            if (!$record->is_claimed) {
                return ApiResponse::validationError(['record' => ['该记录尚未被领取']]);
            }

            $record->update([
                'is_completed' => true,
            ]);

            $record->load(['submitter', 'claimer']);

            return ApiResponse::updated($record, '完成数据记录成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('完成数据记录失败', $e->getMessage());
        }
    }

    /**
     * 标记为重复记录
     *
     * @param int $id
     * @return JsonResponse
     */
    public function markDuplicate(int $id): JsonResponse
    {
        try {
            $record = DataRecord::find($id);

            if (!$record) {
                return ApiResponse::notFound('数据记录不存在');
            }

            // 检查权限：只有领取者可以标记重复
            if ($record->claimer_id !== Auth::id()) {
                return ApiResponse::forbidden('您没有权限标记此记录为重复');
            }

            $record->update([
                'is_duplicate' => true,
            ]);

            $record->load(['submitter', 'claimer']);

            return ApiResponse::updated($record, '标记重复记录成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('标记重复记录失败', $e->getMessage());
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
                // 提交的记录统计
                'submitted' => [
                    'total' => DataRecord::where('submitter_id', $userId)->count(),
                    'claimed' => DataRecord::where('submitter_id', $userId)->where('is_claimed', true)->count(),
                    'completed' => DataRecord::where('submitter_id', $userId)->where('is_completed', true)->count(),
                    'duplicate' => DataRecord::where('submitter_id', $userId)->where('is_duplicate', true)->count(),
                ],
                // 领取的记录统计
                'claimed' => [
                    'total' => DataRecord::where('claimer_id', $userId)->count(),
                    'completed' => DataRecord::where('claimer_id', $userId)->where('is_completed', true)->count(),
                    'duplicate' => DataRecord::where('claimer_id', $userId)->where('is_duplicate', true)->count(),
                ],
                // 按平台统计
                'platforms' => DataRecord::where('submitter_id', $userId)
                    ->selectRaw('platform, COUNT(*) as count')
                    ->groupBy('platform')
                    ->pluck('count', 'platform')
                    ->toArray(),
                // 全局统计（仅供参考）
                'global' => [
                    'total_records' => DataRecord::count(),
                    'unclaimed_records' => DataRecord::where('is_claimed', false)->count(),
                    'completed_records' => DataRecord::where('is_completed', true)->count(),
                    'duplicate_records' => DataRecord::where('is_duplicate', true)->count(),
                ],
            ];

            return ApiResponse::success($stats, '获取统计信息成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('获取统计信息失败', $e->getMessage());
        }
    }
}
