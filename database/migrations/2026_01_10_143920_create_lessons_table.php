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
        Schema::create('lessons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source_project')->index();
            $table->enum('type', ['cursor', 'ai_output', 'manual'])->default('manual');
            $table->string('category')->nullable()->index();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->text('content');
            $table->string('content_hash', 64)->index();
            $table->boolean('is_generic')->default(true)->index();
            $table->timestamps();

            $table->index(['content_hash', 'source_project']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
