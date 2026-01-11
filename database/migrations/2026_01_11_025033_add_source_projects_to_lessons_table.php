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
        Schema::table('lessons', function (Blueprint $table) {
            // Add source_projects JSON column to track all projects that contributed this content
            if (! Schema::hasColumn('lessons', 'source_projects')) {
                $table->json('source_projects')->nullable()->after('source_project');
            }
        });

        // Populate source_projects with existing source_project values
        \DB::table('lessons')->whereNull('source_projects')->update([
            'source_projects' => \DB::raw('JSON_ARRAY(source_project)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (Schema::hasColumn('lessons', 'source_projects')) {
                $table->dropColumn('source_projects');
            }
        });
    }
};
