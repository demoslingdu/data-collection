<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Http\Resources\DataRecordResource;
use App\Models\DataRecord;
use App\Models\DataRecordAssignment;
use App\Models\Company;
use App\Services\DataAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

/**
 * 数据记录控制器
 * 处理数据记录的完整CRUD操作
 */
class DataRecordController extends Controller
{
    /**
     * 数据分发服务
     *
     * @var DataAssignmentService
     */
    protected DataAssignmentService $assignmentService;

    /**
     * 构造函数
     *
     * @param DataAssignmentService $assignmentService
     */
    public function __construct(DataAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }
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

            // ID精确查询（优先级最高）
            if ($request->has('id') && $request->id) {
                $query->where('id', $request->id);
            }

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

            // 时间段查询：支持按创建时间范围筛选
            if ($request->has('start_date') && $request->start_date) {
                try {
                    // 验证日期格式并转换为开始时间（当天00:00:00）
                    $startDate = Carbon::createFromFormat('Y-m-d', $request->start_date)->startOfDay();
                    $query->where('created_at', '>=', $startDate);
                } catch (\Exception $e) {
                    return ApiResponse::validationError(['start_date' => ['开始日期格式不正确，请使用 Y-m-d 格式']]);
                }
            }

            if ($request->has('end_date') && $request->end_date) {
                try {
                    // 验证日期格式并转换为结束时间（当天23:59:59）
                    $endDate = Carbon::createFromFormat('Y-m-d', $request->end_date)->endOfDay();
                    $query->where('created_at', '<=', $endDate);
                } catch (\Exception $e) {
                    return ApiResponse::validationError(['end_date' => ['结束日期格式不正确，请使用 Y-m-d 格式']]);
                }
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

            // 使用资源类格式化响应，确保时间字段显示为北京时间
            $formattedRecords = DataRecordResource::collection($records)->response()->getData();

            return ApiResponse::paginated($formattedRecords, '获取数据记录列表成功');

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
                'image_url' => 'nullable|url',
                'platform' => 'required|in:douyin,xiaohongshu,taobao,xianyu',
                'platform_id' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20|regex:/^1[3-9]\d{9}$/',
            ], [
                'image_url.url' => '图片URL格式不正确',
                'platform.required' => '来源平台不能为空',
                'platform.in' => '来源平台必须是：douyin, xiaohongshu, taobao, xianyu',
                'platform_id.required' => '平台ID不能为空',
                'platform_id.max' => '平台ID不能超过255个字符',
                'phone.max' => '手机号不能超过20个字符',
                'phone.regex' => '手机号格式不正确，请输入有效的11位手机号',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            // 根据数据类型选择不同的重复检查策略
            $existingRecord = null;
            $isDuplicate = false;

            // 判断是否有图片URL，决定使用哪种排重策略
            if (empty($request->image_url)) {
                // 情况1：没有图片URL，只有手机号时，针对手机号进行排重
                if (!empty($request->phone)) {
                    $existingRecord = DataRecord::where('phone', $request->phone)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    // if ($existingRecord) {
                    //     // 计算时间差（天数）
                    //     $daysDiff = now()->diffInDays($existingRecord->created_at);
                        
                    //     // 如果在3天内（包括当天），标记为重复
                    //     if ($daysDiff <= 3) {
                    //         $isDuplicate = true;
                    //     }
                    //     // 超过3天的算新客资，不标记重复
                    // }
                }
            } else {
                // 情况2：有图片URL时，使用原来的平台和平台ID进行排重
                $existingRecord = DataRecord::where('platform', $request->platform)
                    ->where('platform_id', $request->platform_id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($existingRecord) {
                    // 计算时间差（天数）
                    $daysDiff = now()->diffInDays($existingRecord->created_at);
                    
                    // 如果在3天内（包括当天），标记为重复
                    if ($daysDiff <= 3) {
                        $isDuplicate = true;
                    }
                    // 超过3天的算新客资，不标记重复
                }
            }

            // 使用数据库事务确保数据一致性
            $record = DB::transaction(function () use ($request, $isDuplicate) {
                // 创建数据记录
                $record = DataRecord::create([
                    'image_url' => $request->image_url,
                    'submitter_id' => Auth::id(),
                    'platform' => $request->platform,
                    'platform_id' => $request->platform_id,
                    'phone' => $request->phone,
                    'is_claimed' => false,
                    'is_completed' => false,
                    'is_duplicate' => $isDuplicate,
                ]);

                // 执行默认分发：将新记录分发给所有活跃公司
                try {
                    // 获取所有活跃公司的ID
                    $activeCompanyIds = Company::active()->pluck('id')->toArray();
                    
                    if (!empty($activeCompanyIds)) {
                        // 调用批量分发服务
                        $this->assignmentService->batchAssign([$record->id], $activeCompanyIds);
                        
                        Log::info('数据记录默认分发成功', [
                            'record_id' => $record->id,
                            'company_count' => count($activeCompanyIds),
                            'company_ids' => $activeCompanyIds
                        ]);
                    } else {
                        Log::warning('没有找到活跃的公司，跳过默认分发', [
                            'record_id' => $record->id
                        ]);
                    }
                } catch (\Exception $e) {
                    // 分发失败不影响记录创建，只记录日志
                    Log::error('数据记录默认分发失败', [
                        'record_id' => $record->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                return $record;
            });

            $record->load(['submitter', 'claimer']);

            // 同步数据到外部接口
            $this->syncToExternalApi($record);

            // 使用资源类格式化响应，确保时间字段显示为北京时间
            return ApiResponse::created(new DataRecordResource($record), '创建数据记录成功');

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

            // 使用资源类格式化响应，确保时间字段显示为北京时间
            return ApiResponse::success(new DataRecordResource($record), '获取数据记录详情成功');

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

            // 使用资源类格式化响应，确保时间字段显示为北京时间
            return ApiResponse::updated(new DataRecordResource($record), '更新数据记录成功');

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
    /**
     * 领取数据记录
     * 基于新的数据分发系统，通过 DataAssignmentService 处理领取操作
     *
     * @param int $id 数据记录ID
     * @return JsonResponse
     */
    public function claim(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // 查找数据记录
            $record = DataRecord::find($id);
            if (!$record) {
                return ApiResponse::notFound('数据记录不存在');
            }

            // 检查是否是自己提交的记录
            if ($record->submitter_id === $user->id) {
                return ApiResponse::validationError(['record' => ['不能领取自己提交的记录']]);
            }

            // 查找当前用户公司的可领取分发记录
            $assignment = DataRecordAssignment::where('data_record_id', $id)
                ->where('company_id', $user->company_id)
                ->where('is_claimed', false)
                ->where(function ($query) use ($user) {
                    // 可以领取的数据：assigned_to 为 null 或者 assigned_to 为当前用户
                    $query->whereNull('assigned_to')->orWhere('assigned_to', $user->id);
                })
                ->first();

            if (!$assignment) {
                return ApiResponse::validationError(['record' => ['该记录不可领取或已被领取']]);
            }

            // 使用 DataAssignmentService 处理领取操作
            $claimedAssignment = $this->assignmentService->claimAssignment($assignment, $user);

            // 加载关联数据并返回兼容格式
            $record->load(['submitter']);
            $record->is_claimed = true;
            $record->claimer_id = $user->id;
            $record->claimer = $user;

            // 使用资源类格式化响应，确保时间字段显示为北京时间
            return ApiResponse::updated(new DataRecordResource($record), '领取数据记录成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('领取数据记录失败', $e->getMessage());
        }
    }

    /**
     * 完成数据记录
     * 基于新的数据分发系统，通过 DataAssignmentService 处理完成操作
     *
     * @param int $id 数据记录ID
     * @return JsonResponse
     */
    public function complete(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // 查找数据记录
            $record = DataRecord::find($id);
            if (!$record) {
                return ApiResponse::notFound('数据记录不存在');
            }

            // 查找当前用户已领取的分发记录
            $assignment = DataRecordAssignment::where('data_record_id', $id)
                ->where('assigned_to', $user->id)
                ->where('is_claimed', true)
                ->where('is_completed', false)
                ->first();

            if (!$assignment) {
                return ApiResponse::validationError(['record' => ['该记录不存在或您无权完成此记录']]);
            }

            // 使用 DataAssignmentService 处理完成操作
            $completedAssignment = $this->assignmentService->completeAssignment($assignment, $user);

            // 加载关联数据并返回兼容格式
            $record->load(['submitter']);
            $record->is_claimed = true;
            $record->is_completed = true;
            $record->claimer_id = $user->id;
            $record->claimer = $user;

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
            // 使用事务确保数据一致性
            return DB::transaction(function () use ($id) {
                $record = DataRecord::find($id);

                if (!$record) {
                    return ApiResponse::notFound('数据记录不存在');
                }

                $currentUserId = Auth::id();
                $userCompanyId = Auth::user()->company_id;

                // 检查权限：基于 data_record_assignments 表验证
                $assignment = DataRecordAssignment::where('data_record_id', $id)
                    ->where('company_id', $userCompanyId)
                    ->where('assigned_to', $currentUserId)
                    ->where('is_claimed', true)
                    ->first();

                if (!$assignment) {
                    return ApiResponse::forbidden('您没有权限标记此记录为重复，请确保您已领取该数据');
                }

                if ($assignment->is_completed) {
                    return ApiResponse::badRequest('该数据已完成处理，无法标记为重复');
                }

                // 同时更新 data_records 和 data_record_assignments 表
                $record->update([
                    'is_duplicate' => true,
                ]);

                $assignment->update([
                    'is_completed' => true, // 标记为已完成，状态为重复
                ]);

                // 加载关联数据，包含分发记录信息
                $record->load([
                    'submitter', 
                    'claimer',
                    'assignments' => function ($query) use ($userCompanyId) {
                        $query->where('company_id', $userCompanyId)
                              ->with('assignedTo');
                    }
                ]);

                // 使用资源类格式化响应，确保时间字段显示为北京时间
                return ApiResponse::updated([
                    'record' => new DataRecordResource($record),
                    'assignment' => $assignment->fresh(['assignedTo']),
                ], '标记重复记录成功');
            });

        } catch (\Exception $e) {
            Log::error('标记重复记录失败', [
                'data_record_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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

    /**
     * 获取当前用户所属企业的未领取数据分发列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unclaimed(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // 获取分页参数
            $perPage = $request->get('per_page', 15);
            
            // 使用 DataAssignmentService 获取可领取的分发数据
            $assignments = $this->assignmentService->getClaimableAssignments($user, [], $perPage);
            
            // 转换数据格式以匹配前端期望的结构，使用资源类格式化时间
            $transformedData = $assignments->getCollection()->map(function ($assignment) {
                $recordResource = new DataRecordResource($assignment->dataRecord);
                $recordData = $recordResource->toArray(request());
                
                return [
                    'id' => $recordData['id'],
                    'image_url' => $recordData['image_url'],
                    'platform' => $recordData['platform'],
                    'platform_id' => $recordData['platform_id'],
                    'phone' => $recordData['phone'],
                    'submitter' => $recordData['submitter'],
                    'created_at' => $recordData['created_at'], // 已经格式化为北京时间
                    'assignment_id' => $assignment->id, // 添加分发记录ID用于后续操作
                ];
            });
            
            // 构建分页响应
            $paginatedResponse = new \Illuminate\Pagination\LengthAwarePaginator(
                $transformedData,
                $assignments->total(),
                $assignments->perPage(),
                $assignments->currentPage(),
                [
                    'path' => $request->url(),
                    'pageName' => 'page',
                ]
            );

            return ApiResponse::paginated($paginatedResponse, '获取企业可领取数据列表成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('获取企业可领取数据列表失败', $e->getMessage());
        }
    }

    /**
     * 获取当前用户已领取但未完成的分发数据列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function myClaimed(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // 获取分页参数
            $perPage = $request->get('per_page', 15);
            
            // 使用 DataAssignmentService 获取用户已领取未完成的分发数据
            $assignments = $this->assignmentService->getUserClaimedIncompleteAssignments($user, [], $perPage);
            
            // 转换数据格式以匹配前端期望的结构
            $transformedData = $assignments->getCollection()->map(function ($assignment) {
                return [
                    'id' => $assignment->dataRecord->id,
                    'image_url' => $assignment->dataRecord->image_url,
                    'platform' => $assignment->dataRecord->platform,
                    'platform_id' => $assignment->dataRecord->platform_id,
                    'phone' => $assignment->dataRecord->phone,
                    'submitter' => $assignment->dataRecord->submitter,
                    'created_at' => $assignment->dataRecord->created_at,
                    'claimed_at' => $assignment->claimed_at,
                    'assignment_id' => $assignment->id, // 添加分发记录ID用于后续操作
                ];
            });
            
            // 构建分页响应
            $paginatedResponse = new \Illuminate\Pagination\LengthAwarePaginator(
                $transformedData,
                $assignments->total(),
                $assignments->perPage(),
                $assignments->currentPage(),
                [
                    'path' => $request->url(),
                    'pageName' => 'page',
                ]
            );

            return ApiResponse::paginated($paginatedResponse, '获取我已领取未完成数据列表成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('获取我已领取未完成数据列表失败', $e->getMessage());
        }
    }

    /**
     * 同步数据到外部接口
     * 
     * @param DataRecord $record 数据记录
     * @return void
     */
    private function syncToExternalApi(DataRecord $record): void
    {
        try {
            // 检查是否有手机号
            if (empty($record->phone)) {
                Log::info('数据记录没有手机号，跳过外部接口同步', [
                    'record_id' => $record->id
                ]);
                return;
            }

            // 检查手机号是否重复（在过去已同步的记录中）
            $existingSyncedRecord = DataRecord::where('phone', $record->phone)
                ->where('id', '!=', $record->id)
                ->where('synced_to_external', true)
                ->first();

            if ($existingSyncedRecord) {
                Log::info('手机号已存在于已同步记录中，跳过外部接口同步', [
                    'record_id' => $record->id,
                    'phone' => $record->phone,
                    'existing_record_id' => $existingSyncedRecord->id
                ]);
                return;
            }

            // 准备同步数据
            $syncData = [
                'sn' => $record->phone,
                'sn_info' => '',
                'buy_nick' => '',
                'buy_id' => '',
                'buy_price' => '',
                'sell_name' => '抖音',
                'create_time' => '',
                'pay_time' => '',
                'info' => '',
                'mobile' => $record->phone,
                'addr' => '',
                'buyer_name' => '',
                'remark' => '',
                'order_status' => '',
                'status' => 12
            ];

            // 发送请求到外部接口
            $response = Http::timeout(30)->post('http://47.98.194.116:2000/api/gfData', $syncData);

            if ($response->successful()) {
                // 标记为已同步
                $record->update(['synced_to_external' => true]);
                
                Log::info('数据记录同步到外部接口成功', [
                    'record_id' => $record->id,
                    'phone' => $record->phone,
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
            } else {
                Log::error('数据记录同步到外部接口失败', [
                    'record_id' => $record->id,
                    'phone' => $record->phone,
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('数据记录同步到外部接口异常', [
                'record_id' => $record->id,
                'phone' => $record->phone ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
