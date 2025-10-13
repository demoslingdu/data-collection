<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 创建数据记录分发表
     */
    public function up(): void
    {
        Schema::create('data_record_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_record_id')->constrained('data_records')->onDelete('cascade')->comment('数据记录ID');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade')->comment('公司ID');
            $table->foreignId('assigned_by')->constrained('users')->comment('分发人ID');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->comment('处理人ID');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'rejected'])->default('pending')->comment('处理状态');
            $table->text('notes')->nullable()->comment('备注信息');
            $table->timestamp('assigned_at')->useCurrent()->comment('分发时间');
            $table->timestamp('started_at')->nullable()->comment('开始处理时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamps();
            
            // 索引
            $table->index(['data_record_id', 'company_id']);
            $table->index(['company_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('status');
            
            // 唯一约束：同一数据记录不能重复分发给同一公司
            $table->unique(['data_record_id', 'company_id']);
        });
    }

    /**
     * 回滚迁移
     */
    public function down(): void
    {
        Schema::dropIfExists('data_record_assignments');
    }
};
