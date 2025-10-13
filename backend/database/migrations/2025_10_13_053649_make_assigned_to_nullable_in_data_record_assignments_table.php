<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 将 assigned_to 字段改为可空
     */
    public function up(): void
    {
        Schema::table('data_record_assignments', function (Blueprint $table) {
            // 修改 assigned_to 字段为可空，表示数据分发给公司但未指定具体处理人
            $table->foreignId('assigned_to')->nullable()->change()->comment('领取人ID（可为空）');
        });
    }

    /**
     * 回滚迁移 - 将 assigned_to 字段改为不可空
     */
    public function down(): void
    {
        Schema::table('data_record_assignments', function (Blueprint $table) {
            // 回滚时将 assigned_to 字段改为不可空
            $table->foreignId('assigned_to')->nullable(false)->change()->comment('领取人ID');
        });
    }
};
