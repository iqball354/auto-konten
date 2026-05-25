<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE post_detail MODIFY media_type ENUM('image','video') NULL");
        DB::statement('ALTER TABLE post_detail MODIFY file_path VARCHAR(500) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('post_detail')->whereNull('media_type')->update(['media_type' => 'image']);
        DB::table('post_detail')->whereNull('file_path')->update(['file_path' => 'text-only']);

        DB::statement("ALTER TABLE post_detail MODIFY media_type ENUM('image','video') NOT NULL");
        DB::statement('ALTER TABLE post_detail MODIFY file_path VARCHAR(500) NOT NULL');
    }
};
