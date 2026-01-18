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
            // Add title and summary columns for structured metadata
            $table->string('title')->nullable()->after('category');
            $table->text('summary')->nullable()->after('title');

            // Add FULLTEXT index on content column for better search
            $table->fullText('content');

            // Add performance indexes
            $table->index(['category', 'subcategory', 'is_generic'], 'idx_category_subcategory_generic');
            $table->index(['created_at'], 'idx_created_at_desc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_category_subcategory_generic');
            $table->dropIndex('idx_created_at_desc');
            $table->dropFullText(['content']);

            // Drop columns
            $table->dropColumn(['title', 'summary']);
        });
    }
};
