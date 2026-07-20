<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the questions table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            // Soft-delete of categories is handled in the model; null FK on category removal.
            $table->foreignId('category_id')->nullable()->constrained('question_categories')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // Content
            $table->string('title')->nullable();
            $table->text('body');
            $table->string('type')->default('mcq'); // mcq | true_false | short_answer | long_answer | fill_blank
            $table->boolean('allows_multiple')->default(false);
            $table->json('options')->nullable();
            $table->text('correct_answer')->nullable();
            $table->json('correct_answers')->nullable();
            $table->text('explanation')->nullable();
            $table->string('reference')->nullable();

            // Scoring & Classification
            $table->string('marks_type')->default('single');
            $table->json('marks_list')->nullable();
            $table->unsignedTinyInteger('marks')->default(1);
            $table->string('difficulty')->default('medium'); // easy | medium | hard | very_hard
            $table->string('status')->default('active'); // active | inactive | suspended
            $table->boolean('is_public')->default(false);
            $table->boolean('show_explanation_publicly')->default(true);
            $table->unsignedInteger('view_count')->default(0);
            $table->json('public_tags')->nullable();

            // SEO / Metadata
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('slug')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            // FK added after galleries table exists (see create_galleries_table).
            $table->foreignId('og_image_id')->nullable();
            $table->string('robots')->default('index,follow');
            $table->text('schema_markup')->nullable();
            $table->boolean('ai_generated')->default(false);
            $table->boolean('ai_improve')->default(false);

            // Audit
            $table->json('updated_by_history')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'category_id']);
            $table->index(['organization_id', 'type']);
            $table->index(['organization_id', 'difficulty']);
            $table->index(['organization_id', 'is_public', 'status']);
            $table->unique(['organization_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
