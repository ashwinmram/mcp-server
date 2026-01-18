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
        // MySQL allows MODIFY COLUMN with AFTER to change position
        // This preserves existing data
        DB::statement('ALTER TABLE `lessons` MODIFY COLUMN `subcategory` VARCHAR(255) NULL AFTER `category`');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Move it back to after summary (original position)
        DB::statement('ALTER TABLE `lessons` MODIFY COLUMN `subcategory` VARCHAR(255) NULL AFTER `summary`');
    }
};
