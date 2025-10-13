<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 执行迁移 - 移除 assigned_by、status、notes 字段
     */
    public function up(): void
    {
        Schema::table('data_record_assignments', function (Blueprint $table) {
            // 先删除外键约束
            $table->dropForeign(['assigned_by']);
            // 移除 assigned_by 字段
            $table->dropColumn('assigned_by');
            // 移除 status 字段
            $table->dropColumn('status');
            // 移除 notes 字段
            $table->dropColumn('notes');
        });
    }

    /**
     * 回滚迁移 - 重新添加 assigned_by、status、notes 字段
     */
    public function down(): void
    {
        Schema::table('data_record_assignments', function (Blueprint $table) {
            // 重新添加 assigned_by 字段
            $table->unsignedBigInteger('assigned_by')->nullable()->comment('分配人ID');
            // 重新添加 status 字段
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])
                  ->default('pending')->comment('分配状态');
            // 重新添加 notes 字段
            $table->text('notes')->nullable()->comment('备注信息');
            // 重新添加外键约束
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
        });
    }
};
