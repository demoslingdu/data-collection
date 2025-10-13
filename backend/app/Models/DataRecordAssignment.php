<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 数据记录分发模型
 * 
 * @property int $id 分发ID
 * @property int $data_record_id 数据记录ID
 * @property int $company_id 公司ID
 * @property int $assigned_by 分发人ID
 * @property int|null $assigned_to 处理人ID
 * @property string $status 处理状态
 * @property string|null $notes 备注信息
 * @property \Carbon\Carbon $assigned_at 分发时间
 * @property \Carbon\Carbon|null $started_at 开始处理时间
 * @property \Carbon\Carbon|null $completed_at 完成时间
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 */
class DataRecordAssignment extends Model
{
    /**
     * 处理状态常量
     */
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';

    /**
     * 可批量赋值的属性
     */
    protected $fillable = [
        'data_record_id',
        'company_id',
        'assigned_by',
        'assigned_to',
        'status',
        'notes',
        'assigned_at',
        'started_at',
        'completed_at',
    ];

    /**
     * 属性类型转换
     */
    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 获取数据记录
     */
    public function dataRecord(): BelongsTo
    {
        return $this->belongsTo(DataRecord::class);
    }

    /**
     * 获取公司
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * 获取分发人
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * 获取处理人
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * 作用域：根据状态查询
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 作用域：根据公司查询
     */
    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * 作用域：根据处理人查询
     */
    public function scopeByAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * 作用域：待处理的分发
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * 作用域：处理中的分发
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * 作用域：已完成的分发
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * 获取状态描述
     */
    public function getStatusDescriptionAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => '待处理',
            self::STATUS_IN_PROGRESS => '处理中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_REJECTED => '已拒绝',
            default => '未知状态',
        };
    }

    /**
     * 获取所有可用状态
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING => '待处理',
            self::STATUS_IN_PROGRESS => '处理中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_REJECTED => '已拒绝',
        ];
    }
}
