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
        Schema::create('lesson_relationships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lesson_id');
            $table->uuid('related_lesson_id');
            $table->enum('relationship_type', ['prerequisite', 'related', 'alternative', 'supersedes'])->default('related');
            $table->float('relevance_score')->nullable();
            $table->timestamps();

            $table->foreign('lesson_id')->references('id')->on('lessons')->onDelete('cascade');
            $table->foreign('related_lesson_id')->references('id')->on('lessons')->onDelete('cascade');
            $table->unique(['lesson_id', 'related_lesson_id', 'relationship_type'], 'unique_lesson_relationship');
            $table->index(['lesson_id', 'relationship_type'], 'idx_lesson_relationship_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_relationships');
    }
};
