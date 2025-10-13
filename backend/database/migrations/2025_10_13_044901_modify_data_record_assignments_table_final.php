<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 修改数据记录分发表以支持多用户分发 - 最终版本
     */
    public function up(): void
    {
        // 首先检查表结构，只添加不存在的字段
        $columns = collect(DB::select("SHOW COLUMNS FROM data_record_assignments"))->pluck('Field');
        
        Schema::table('data_record_assignments', function (Blueprint $table) use ($columns) {
            // 检查并移除现有的唯一约束（如果存在）
            $indexes = DB::select("SHOW INDEX FROM data_record_assignments WHERE Key_name = 'data_record_assignments_data_record_id_company_id_unique'");
            if (!empty($indexes)) {
                $table->dropUnique(['data_record_id', 'company_id']);
            }
            
            // 修改 assigned_to 字段为必填（NOT NULL）
            $table->foreignId('assigned_to')->nullable(false)->change()->comment('领取人ID');
            
            // 只添加不存在的字段
            if (!$columns->contains('is_claimed')) {
                $table->boolean('is_claimed')->default(false)->comment('是否已领取');
            }
            
            if (!$columns->contains('claimed_at')) {
                $table->timestamp('claimed_at')->nullable()->comment('领取时间');
            }
            
            if (!$columns->contains('is_completed')) {
                $table->boolean('is_completed')->default(false)->comment('是否已完成');
            }
            
            // 添加新的唯一约束：同一数据记录不能重复分发给同一公司的同一用户
            $table->unique(['data_record_id', 'company_id', 'assigned_to'], 'unique_data_company_user');
            
            // 添加新的索引
            $table->index(['assigned_to', 'is_claimed']);
            $table->index(['assigned_to', 'is_completed']);
            $table->index(['company_id', 'is_claimed']);
        });
    }

    /**
     * 回滚迁移
     */
    public function down(): void
    {
        Schema::table('data_record_assignments', function (Blueprint $table) {
            // 移除新添加的唯一约束
            $table->dropUnique('unique_data_company_user');
            
            // 移除新添加的索引
            $table->dropIndex(['assigned_to', 'is_claimed']);
            $table->dropIndex(['assigned_to', 'is_completed']);
            $table->dropIndex(['company_id', 'is_claimed']);
            
            // 移除新添加的字段
            $table->dropColumn(['is_claimed', 'claimed_at', 'is_completed']);
            
            // 恢复 assigned_to 字段为可空
            $table->foreignId('assigned_to')->nullable()->change()->comment('处理人ID');
            
            // 恢复原有的唯一约束
            $table->unique(['data_record_id', 'company_id']);
        });
    }
};
