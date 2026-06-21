<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the question_categories table.
 *
 * Renamed from "categories" to "question_categories" to avoid conflicts
 * with future category modules (exam categories, course categories, etc.).
 *
 * Columns:
 *   - Core        : organization_id, parent_id (self-referencing), name, description, status
 *   - SEO/Meta    : meta_title, meta_description, meta_keywords, slug, canonical_url, og_title, og_description
 *   - AI flags    : ai_generated (content created by AI), ai_improve (queued for AI improvement)
 *   - Audit       : created_by, updated_by, updated_by_history, timestamps, soft_deletes
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_categories', function (Blueprint $table) {
            $table->id();

            // ── Relations ──────────────────────────────────────────────────────
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('question_categories')->cascadeOnDelete();

            // ── Content ────────────────────────────────────────────────────────
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active | inactive | suspended

            // ── SEO / Metadata ─────────────────────────────────────────────────
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('slug')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();

            // ── AI flags (UI prepared; no AI logic yet) ────────────────────────
            $table->boolean('ai_generated')->default(false); // category content was AI-generated
            $table->boolean('ai_improve')->default(false);   // category queued for AI improvement

            // ── Audit ──────────────────────────────────────────────────────────
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('updated_by_history')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_categories');
    }
};
