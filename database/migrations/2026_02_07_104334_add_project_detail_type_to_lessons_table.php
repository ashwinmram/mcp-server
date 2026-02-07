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
        DB::statement("ALTER TABLE lessons MODIFY COLUMN type ENUM('cursor', 'ai_output', 'manual', 'markdown', 'project_detail') DEFAULT 'manual'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('lessons')->where('type', 'project_detail')->update(['type' => 'manual']);
        DB::statement("ALTER TABLE lessons MODIFY COLUMN type ENUM('cursor', 'ai_output', 'manual', 'markdown') DEFAULT 'manual'");
    }
};
