<?php

namespace App\Services;

use App\Models\DataRecord;
use App\Models\DataRecordAssignment;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 数据分发服务类
 * 处理数据分发相关的业务逻辑
 */
class DataAssignmentService
{
    /**
     * 获取数据分发列表（带分页和过滤）
     */
    public function getAssignments(array $filters = [], int $perPage = 15, ?User $user = null): LengthAwarePaginator
    {
        $query = DataRecordAssignment::with(['dataRecord', 'company', 'assignedBy', 'assignedTo']);

        // 根据用户角色过滤数据
        if ($user && $user->role !== 'admin') {
            $query->byCompany($user->company_id);
        }

        // 状态过滤
        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        // 公司过滤
        if (!empty($filters['company_id'])) {
            $query->byCompany($filters['company_id']);
        }

        // 分配给指定用户的过滤
        if (!empty($filters['assigned_to'])) {
            $query->byAssignedTo($filters['assigned_to']);
        }

        // 日期范围过滤
        if (!empty($filters['date_from'])) {
            $query->where('assigned_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('assigned_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('assigned_at', 'desc')->paginate($perPage);
    }

    /**
     * 创建数据分发（单个公司）
     */
    public function createAssignment(array $dataRecordIds, int $companyId, User $assignedBy, ?int $assignedTo = null, ?string $notes = null): array
    {
        $assignments = [];

        DB::transaction(function () use ($dataRecordIds, $companyId, $assignedBy, $assignedTo, $notes, &$assignments) {
            foreach ($dataRecordIds as $dataRecordId) {
                // 如果指定了领取人，检查是否已经分发给该公司的该用户
                if ($assignedTo) {
                    $existingAssignment = DataRecordAssignment::where('data_record_id', $dataRecordId)
                        ->where('company_id', $companyId)
                        ->where('assigned_to', $assignedTo)
                        ->first();

                    if ($existingAssignment) {
                        continue; // 跳过已分发的记录
                    }
                }

                $assignment = DataRecordAssignment::create([
                    'data_record_id' => $dataRecordId,
                    'company_id' => $companyId,
                    'assigned_by' => $assignedBy->id,
                    'assigned_to' => $assignedTo,
                    'assigned_at' => now(),
                    'status' => 'pending',
                    'notes' => $notes,
                    'is_claimed' => false,
                    'is_completed' => false,
                ]);

                $assignments[] = $assignment;
            }
        });

        return $assignments;
    }

    /**
     * 批量分发数据到多个公司
     */
    public function batchAssign(array $dataRecordIds, array $companyIds, User $assignedBy, ?string $notes = null): array
    {
        $assignments = [];

        DB::transaction(function () use ($dataRecordIds, $companyIds, $assignedBy, $notes, &$assignments) {
            foreach ($dataRecordIds as $dataRecordId) {
                foreach ($companyIds as $companyId) {
                    $assignment = DataRecordAssignment::create([
                        'data_record_id' => $dataRecordId,
                        'company_id' => $companyId,
                        'assigned_by' => $assignedBy->id,
                        'assigned_to' => $assignedBy->id, // 默认分发人为领取人
                        'assigned_at' => now(),
                        'status' => 'pending',
                        'notes' => $notes,
                        'is_claimed' => false,
                        'is_completed' => false,
                    ]);

                    $assignments[] = $assignment;
                }
            }
        });

        return $assignments;
    }

    /**
     * 批量分发数据到多个用户
     */
    public function batchAssignToUsers(array $dataRecordIds, array $userIds, User $assignedBy, ?string $notes = null): array
    {
        $assignments = [];

        DB::transaction(function () use ($dataRecordIds, $userIds, $assignedBy, $notes, &$assignments) {
            foreach ($dataRecordIds as $dataRecordId) {
                foreach ($userIds as $userId) {
                    // 获取用户信息以确定公司ID
                    $user = User::find($userId);
                    if (!$user) {
                        continue;
                    }

                    // 检查是否已经分发给该公司的该用户
                    $existingAssignment = DataRecordAssignment::where('data_record_id', $dataRecordId)
                        ->where('company_id', $user->company_id)
                        ->where('assigned_to', $userId)
                        ->first();

                    if ($existingAssignment) {
                        continue; // 跳过已分发的记录
                    }

                    $assignment = DataRecordAssignment::create([
                        'data_record_id' => $dataRecordId,
                        'company_id' => $user->company_id,
                        'assigned_by' => $assignedBy->id,
                        'assigned_to' => $userId,
                        'assigned_at' => now(),
                        'status' => 'pending',
                        'notes' => $notes,
                        'is_claimed' => false,
                        'is_completed' => false,
                    ]);

                    $assignments[] = $assignment;
                }
            }
        });

        return $assignments;
    }

    /**
     * 更新分发状态
     */
    public function updateAssignmentStatus(DataRecordAssignment $assignment, string $status, ?int $assignedTo = null, ?string $notes = null): DataRecordAssignment
    {
        $updateData = [
            'status' => $status,
            'notes' => $notes,
        ];

        if ($assignedTo !== null) {
            $updateData['assigned_to'] = $assignedTo;
        }

        // 更新状态时设置完成标记
        if ($status === 'completed' && $assignment->status !== 'completed') {
            $updateData['is_completed'] = true;
        }

        $assignment->update($updateData);
        return $assignment->fresh();
    }

    /**
     * 用户领取分发数据
     */
    public function claimAssignment(DataRecordAssignment $assignment, User $user): DataRecordAssignment
    {
        // 检查是否可以领取
        if ($assignment->is_claimed) {
            throw new \Exception('该数据已被领取');
        }

        if ($assignment->assigned_to !== $user->id) {
            throw new \Exception('您无权领取该数据');
        }

        $assignment->update([
            'is_claimed' => true,
            'claimed_at' => now(),
            'status' => 'in_progress',
        ]);

        return $assignment->fresh();
    }

    /**
     * 完成数据处理
     */
    public function completeAssignment(DataRecordAssignment $assignment, User $user, ?string $notes = null): DataRecordAssignment
    {
        // 检查是否可以完成
        if (!$assignment->is_claimed) {
            throw new \Exception('请先领取该数据');
        }

        if ($assignment->assigned_to !== $user->id) {
            throw new \Exception('您无权完成该数据处理');
        }

        if ($assignment->is_completed) {
            throw new \Exception('该数据已完成处理');
        }

        $assignment->update([
            'is_completed' => true,
            'status' => 'completed',
            'notes' => $notes ?? $assignment->notes,
        ]);

        return $assignment->fresh();
    }

    /**
     * 获取用户可领取的分发数据
     */
    public function getClaimableAssignments(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DataRecordAssignment::with(['dataRecord', 'company', 'assignedBy'])
            ->where('assigned_to', $user->id)
            ->where('is_claimed', false);

        // 状态过滤
        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        // 日期范围过滤
        if (!empty($filters['date_from'])) {
            $query->where('assigned_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('assigned_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('assigned_at', 'desc')->paginate($perPage);
    }

    /**
     * 获取分发统计信息
     */
    public function getStatistics(array $filters = [], ?User $user = null): array
    {
        $query = DataRecordAssignment::query();

        // 根据用户角色过滤数据
        if ($user && $user->role !== 'admin') {
            $query->byCompany($user->company_id);
        }

        // 日期范围过滤
        if (!empty($filters['date_from'])) {
            $query->where('assigned_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('assigned_at', '<=', $filters['date_to']);
        }

        return [
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
    }

    /**
     * 获取可分发的数据记录
     */
    public function getAvailableRecords(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DataRecord::query();

        // 搜索过滤
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 平台过滤
        if (!empty($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        // 状态过滤
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * 检查数据记录是否已分发给指定公司的指定用户
     */
    public function isAssignedToUser(int $dataRecordId, int $companyId, int $userId): bool
    {
        return DataRecordAssignment::where('data_record_id', $dataRecordId)
            ->where('company_id', $companyId)
            ->where('assigned_to', $userId)
            ->exists();
    }

    /**
     * 检查数据记录是否已分发给指定公司
     */
    public function isAssignedToCompany(int $dataRecordId, int $companyId): bool
    {
        return DataRecordAssignment::where('data_record_id', $dataRecordId)
            ->where('company_id', $companyId)
            ->exists();
    }

    /**
     * 获取数据记录的分发状态
     */
    public function getAssignmentStatus(int $dataRecordId, int $companyId): ?string
    {
        $assignment = DataRecordAssignment::where('data_record_id', $dataRecordId)
            ->where('company_id', $companyId)
            ->first();

        return $assignment ? $assignment->status : null;
    }

    /**
     * 获取公司的分发统计
     */
    public function getCompanyAssignmentStats(int $companyId, array $filters = []): array
    {
        $query = DataRecordAssignment::byCompany($companyId);

        // 日期范围过滤
        if (!empty($filters['date_from'])) {
            $query->where('assigned_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('assigned_at', '<=', $filters['date_to']);
        }

        return [
            'total' => $query->count(),
            'pending' => $query->clone()->pending()->count(),
            'in_progress' => $query->clone()->inProgress()->count(),
            'completed' => $query->clone()->completed()->count(),
            'completion_rate' => $this->calculateCompletionRate($query),
        ];
    }

    /**
     * 计算完成率
     */
    private function calculateCompletionRate($query): float
    {
        $total = $query->count();
        if ($total === 0) {
            return 0.0;
        }

        $completed = $query->clone()->completed()->count();
        return round(($completed / $total) * 100, 2);
    }

    /**
     * 删除分发记录
     */
    public function deleteAssignment(DataRecordAssignment $assignment): bool
    {
        return $assignment->delete();
    }

    /**
     * 获取用户的分发任务
     */
    public function getUserAssignments(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DataRecordAssignment::with(['dataRecord', 'company', 'assignedBy'])
            ->byAssignedTo($userId);

        // 状态过滤
        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        // 日期范围过滤
        if (!empty($filters['date_from'])) {
            $query->where('assigned_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('assigned_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('assigned_at', 'desc')->paginate($perPage);
    }
}