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
 * @property int $assigned_to 领取人ID
 * @property \Carbon\Carbon $assigned_at 分发时间
 * @property bool $is_claimed 是否已领取
 * @property \Carbon\Carbon|null $claimed_at 领取时间
 * @property bool $is_completed 是否已完成
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 */
class DataRecordAssignment extends Model
{
    /**
     * 可批量赋值的属性
     */
    protected $fillable = [
        'data_record_id',
        'company_id',
        'assigned_to',
        'assigned_at',
        'is_claimed',
        'claimed_at',
        'is_completed',
    ];

    /**
     * 属性类型转换
     */
    protected $casts = [
        'assigned_at' => 'datetime',
        'claimed_at' => 'datetime',
        'is_claimed' => 'boolean',
        'is_completed' => 'boolean',
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
     * 获取领取人
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }



    /**
     * 作用域：根据公司查询
     */
    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * 作用域：根据领取人查询
     */
    public function scopeByAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }



    /**
     * 作用域：已领取的分发
     */
    public function scopeClaimed($query)
    {
        return $query->where('is_claimed', true);
    }

    /**
     * 作用域：未领取的分发
     */
    public function scopeUnclaimed($query)
    {
        return $query->where('is_claimed', false);
    }

    /**
     * 作用域：已完成处理的分发
     */
    public function scopeProcessCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    /**
     * 作用域：未完成处理的分发
     */
    public function scopeProcessIncomplete($query)
    {
        return $query->where('is_completed', false);
    }


}
