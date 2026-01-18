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
            $table->float('relevance_score')->default(0.0)->after('is_generic');
            $table->timestamp('deprecated_at')->nullable()->after('relevance_score');
            $table->uuid('superseded_by_lesson_id')->nullable()->after('deprecated_at');

            $table->foreign('superseded_by_lesson_id')->references('id')->on('lessons')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropForeign(['superseded_by_lesson_id']);
            $table->dropColumn(['relevance_score', 'deprecated_at', 'superseded_by_lesson_id']);
        });
    }
};
