<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 数据记录模型
 * 管理数据记录的创建、更新和查询
 */
class DataRecord extends Model
{
    use HasFactory;

    /**
     * 可批量赋值的属性
     *
     * @var array<string>
     */
    protected $fillable = [
        'image_url',
        'submitter_id',
        'platform',
        'platform_id',
        'phone',
        'is_claimed',
        'is_completed',
        'claimer_id',
        'is_duplicate',
    ];

    /**
     * 属性类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_claimed' => 'boolean',
        'is_completed' => 'boolean',
        'is_duplicate' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 平台选项
     */
    const PLATFORM_DOUYIN = 'douyin';
    const PLATFORM_XIAOHONGSHU = 'xiaohongshu';
    const PLATFORM_TAOBAO = 'taobao';
    const PLATFORM_XIANYU = 'xianyu';

    const PLATFORMS = [
        self::PLATFORM_DOUYIN => '抖音',
        self::PLATFORM_XIAOHONGSHU => '小红书',
        self::PLATFORM_TAOBAO => '淘宝',
        self::PLATFORM_XIANYU => '闲鱼',
    ];

    /**
     * 提交者关联
     *
     * @return BelongsTo
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitter_id');
    }

    /**
     * 领取者关联
     *
     * @return BelongsTo
     */
    public function claimer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimer_id');
    }

    /**
     * 获取平台中文名称
     *
     * @return string
     */
    public function getPlatformNameAttribute(): string
    {
        return self::PLATFORMS[$this->platform] ?? $this->platform;
    }

    /**
     * 获取状态描述
     *
     * @return string
     */
    public function getStatusDescriptionAttribute(): string
    {
        if ($this->is_duplicate) {
            return '重复记录';
        }
        
        if ($this->is_completed) {
            return '已完成';
        }
        
        if ($this->is_claimed) {
            return '已领取';
        }
        
        return '待领取';
    }

    /**
     * 作用域：按平台筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $platform
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * 作用域：按提交者筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $submitterId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySubmitter($query, int $submitterId)
    {
        return $query->where('submitter_id', $submitterId);
    }

    /**
     * 作用域：按领取者筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $claimerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByClaimer($query, int $claimerId)
    {
        return $query->where('claimer_id', $claimerId);
    }

    /**
     * 作用域：未领取的记录
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnclaimed($query)
    {
        return $query->where('is_claimed', false);
    }

    /**
     * 作用域：已领取的记录
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClaimed($query)
    {
        return $query->where('is_claimed', true);
    }

    /**
     * 作用域：已完成的记录
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    /**
     * 作用域：重复记录
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDuplicate($query)
    {
        return $query->where('is_duplicate', true);
    }
}
