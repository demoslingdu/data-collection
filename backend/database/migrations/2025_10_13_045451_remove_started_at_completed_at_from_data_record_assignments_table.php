<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 移除 started_at 和 completed_at 字段
     */
    public function up(): void
    {
        Schema::table('data_record_assignments', function (Blueprint $table) {
            // 移除 started_at 字段
            $table->dropColumn('started_at');
            // 移除 completed_at 字段
            $table->dropColumn('completed_at');
        });
    }

    /**
     * 回滚迁移 - 恢复 started_at 和 completed_at 字段
     */
    public function down(): void
    {
        Schema::table('data_record_assignments', function (Blueprint $table) {
            // 恢复 started_at 字段
            $table->timestamp('started_at')->nullable()->comment('开始处理时间');
            // 恢复 completed_at 字段
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
        });
    }
};
