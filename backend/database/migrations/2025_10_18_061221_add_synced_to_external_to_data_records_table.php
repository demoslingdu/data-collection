<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('data_records', function (Blueprint $table) {
            $table->boolean('synced_to_external')->default(false)->comment('是否已同步到外部接口');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_records', function (Blueprint $table) {
            $table->dropColumn('synced_to_external');
        });
    }
};
