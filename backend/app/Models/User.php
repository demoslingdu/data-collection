<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

/**
 * 用户模型
 * 管理用户信息和角色权限
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * 可批量赋值的属性
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'account',
        'password',
        'role',
        'company_id',
    ];

    /**
     * 序列化时隐藏的属性
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 属性类型转换
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * 检查用户是否为管理员
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * 用户提交的数据记录
     */
    public function submittedRecords(): HasMany
    {
        return $this->hasMany(DataRecord::class, 'submitter_id');
    }

    /**
     * 用户领取的数据记录
     */
    public function claimedRecords(): HasMany
    {
        return $this->hasMany(DataRecord::class, 'claimer_id');
    }

    /**
     * 用户所属公司
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * 用户分发的数据记录
     */
    public function assignedRecords(): HasMany
    {
        return $this->hasMany(DataRecordAssignment::class, 'assigned_by');
    }

    /**
     * 用户处理的数据记录分发
     */
    public function processedAssignments(): HasMany
    {
        return $this->hasMany(DataRecordAssignment::class, 'assigned_to');
    }

    /**
     * 检查用户是否为公司管理员
     */
    public function isCompanyAdmin(): bool
    {
        return $this->role === 'company_admin';
    }

    /**
     * 检查用户是否为处理员
     */
    public function isProcessor(): bool
    {
        return $this->role === 'processor';
    }

    /**
     * 获取用户角色描述
     */
    public function getRoleDescriptionAttribute(): string
    {
        return match($this->role) {
            'admin' => '系统管理员',
            'company_admin' => '公司管理员',
            'processor' => '处理员',
            'user' => '普通用户',
            default => '未知角色',
        };
    }
}
