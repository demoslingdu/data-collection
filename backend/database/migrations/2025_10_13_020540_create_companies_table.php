<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 创建公司表
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('公司名称');
            $table->string('code')->unique()->comment('公司代码');
            $table->text('description')->nullable()->comment('公司描述');
            $table->string('contact_person')->nullable()->comment('联系人');
            $table->string('contact_phone')->nullable()->comment('联系电话');
            $table->string('contact_email')->nullable()->comment('联系邮箱');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->timestamps();
            
            // 索引
            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * 回滚迁移
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
