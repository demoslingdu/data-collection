<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 公司模型
 * 
 * @property int $id 公司ID
 * @property string $name 公司名称
 * @property string $code 公司代码
 * @property string|null $description 公司描述
 * @property string|null $contact_person 联系人
 * @property string|null $contact_phone 联系电话
 * @property string|null $contact_email 联系邮箱
 * @property bool $is_active 是否启用
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 */
class Company extends Model
{
    /**
     * 可批量赋值的属性
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'contact_person',
        'contact_phone',
        'contact_email',
        'is_active',
    ];

    /**
     * 属性类型转换
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 获取公司的用户
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * 获取公司的数据记录分发
     */
    public function dataRecordAssignments(): HasMany
    {
        return $this->hasMany(DataRecordAssignment::class);
    }

    /**
     * 作用域：仅获取启用的公司
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 作用域：根据公司代码查询
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * 获取公司状态描述
     */
    public function getStatusDescriptionAttribute(): string
    {
        return $this->is_active ? '启用' : '禁用';
    }
}
