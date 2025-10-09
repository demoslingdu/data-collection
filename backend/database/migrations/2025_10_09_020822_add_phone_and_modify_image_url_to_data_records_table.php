<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 执行迁移操作
     * 为 data_records 表添加手机号字段并修改 image_url 字段为可空
     */
    public function up(): void
    {
        Schema::table('data_records', function (Blueprint $table) {
            // 添加手机号字段，可为空，最大长度20个字符
            $table->string('phone', 20)->nullable()->comment('手机号');
            
            // 修改 image_url 字段为可空
            $table->string('image_url')->nullable()->change();
        });
    }

    /**
     * 回滚迁移操作
     * 移除手机号字段并恢复 image_url 字段为不可空
     */
    public function down(): void
    {
        Schema::table('data_records', function (Blueprint $table) {
            // 移除手机号字段
            $table->dropColumn('phone');
            
            // 恢复 image_url 字段为不可空（注意：这可能会导致数据丢失）
            $table->string('image_url')->nullable(false)->change();
        });
    }
};
