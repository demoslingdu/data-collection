<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 修改用户表以支持账户系统
     * 添加account字段并移除email相关字段
     */
    public function up(): void
    {
        // 检查account字段是否已存在
        if (!Schema::hasColumn('users', 'account')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('account', 100)->nullable()->after('name')->comment('用户账户名');
            });
        }

        // 更新现有用户的account字段（如果email字段存在则使用email，否则生成默认值）
        if (Schema::hasColumn('users', 'email')) {
            \DB::statement("UPDATE users SET account = COALESCE(email, CONCAT('user_', id)) WHERE account IS NULL OR account = ''");
        } else {
            \DB::statement("UPDATE users SET account = CONCAT('user_', id) WHERE account IS NULL OR account = ''");
        }

        Schema::table('users', function (Blueprint $table) {
            // 设置account字段为非空并添加唯一索引
            if (Schema::hasColumn('users', 'account')) {
                $table->string('account', 100)->nullable(false)->change();
                $table->unique('account');
            }
            
            // 移除email相关字段（如果存在）
            if (Schema::hasColumn('users', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }
        });
    }

    /**
     * 回滚迁移
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 恢复email相关字段
            $table->string('email')->unique()->after('name')->comment('用户邮箱');
            $table->timestamp('email_verified_at')->nullable()->after('email')->comment('邮箱验证时间');
            
            // 移除account字段
            if (Schema::hasColumn('users', 'account')) {
                $table->dropUnique(['account']);
                $table->dropColumn('account');
            }
        });
    }
};
