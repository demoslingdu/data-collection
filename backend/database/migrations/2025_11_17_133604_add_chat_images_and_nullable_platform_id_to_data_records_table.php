<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('data_records', function (Blueprint $table) {
            $table->json('chat_images')->nullable()->after('image_url');
        });

        DB::statement('ALTER TABLE data_records MODIFY platform_id VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_records', function (Blueprint $table) {
            $table->dropColumn('chat_images');
        });

        DB::statement('ALTER TABLE data_records MODIFY platform_id VARCHAR(255) NOT NULL');
    }
};
