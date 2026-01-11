<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the enum column to include 'markdown' type
        // MySQL/MariaDB requires using ALTER TABLE to modify enum columns
        DB::statement("ALTER TABLE lessons MODIFY COLUMN type ENUM('cursor', 'ai_output', 'manual', 'markdown') DEFAULT 'manual'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'markdown' from the enum, but keep existing values
        // Convert any 'markdown' types to 'manual' before removing
        DB::table('lessons')->where('type', 'markdown')->update(['type' => 'manual']);
        DB::statement("ALTER TABLE lessons MODIFY COLUMN type ENUM('cursor', 'ai_output', 'manual') DEFAULT 'manual'");
    }
};
