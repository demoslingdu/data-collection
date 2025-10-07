<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 创建数据记录表
     */
    public function up(): void
    {
        Schema::create('data_records', function (Blueprint $table) {
            $table->id();
            $table->text('image_url')->comment('图片URL');
            $table->unsignedBigInteger('submitter_id')->comment('提交人ID');
            $table->enum('platform', ['douyin', 'xiaohongshu', 'taobao', 'xianyu'])->comment('来源平台');
            $table->string('platform_id')->comment('平台ID');
            $table->boolean('is_claimed')->default(false)->comment('是否被领取');
            $table->boolean('is_completed')->default(false)->comment('是否添加成功');
            $table->unsignedBigInteger('claimer_id')->nullable()->comment('领取人用户ID');
            $table->boolean('is_duplicate')->default(false)->comment('是否重复');
            $table->timestamps();
            
            // 创建索引
            $table->index('submitter_id');
            $table->index('claimer_id');
            $table->index('platform');
            $table->index(['is_claimed', 'is_completed']);
            $table->index('created_at');
            $table->unique(['platform', 'platform_id']);
            
            // 外键约束
            $table->foreign('submitter_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('claimer_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_records');
    }
};
