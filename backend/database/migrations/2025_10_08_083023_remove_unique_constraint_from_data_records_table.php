<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 移除 data_records 表的唯一约束
     * 允许同一平台的平台ID重复
     */
    public function up(): void
    {
        Schema::table('data_records', function (Blueprint $table) {
            // 删除 platform 和 platform_id 的唯一约束
            $table->dropUnique(['platform', 'platform_id']);
        });
    }

    /**
     * 回滚迁移 - 重新添加唯一约束
     */
    public function down(): void
    {
        Schema::table('data_records', function (Blueprint $table) {
            // 重新添加 platform 和 platform_id 的唯一约束
            $table->unique(['platform', 'platform_id']);
        });
    }
};
