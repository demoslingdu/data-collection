<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 为用户表添加公司相关字段
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 检查company_id字段是否已存在
            if (!Schema::hasColumn('users', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('set null')->comment('所属公司ID');
                $table->index('company_id');
            }
        });
        
        // 修改现有的role字段，扩展枚举值
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'user', 'company_admin', 'processor') DEFAULT 'user' COMMENT '用户角色'");
    }

    /**
     * 回滚迁移
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropIndex(['company_id']);
            $table->dropColumn(['company_id']);
        });
        
        // 恢复原始的role字段枚举值
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'user') DEFAULT 'user' COMMENT '用户角色'");
    }
};
