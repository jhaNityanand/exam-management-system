<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supplementary tables that depend on core exams / questions / users:
 *  - exam_parts (per-part configuration, categories, distribution)
 *  - exam_part_question (part ↔ question pivot)
 *  - exam_part_question_category (part ↔ question-category selection)
 *  - user_app_settings (per-user UI preferences)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);

            // Scoring / volume
            $table->unsignedSmallInteger('total_questions')->nullable();
            $table->unsignedSmallInteger('total_marks')->nullable();

            // Question selection mode
            $table->boolean('use_question_pool')->default(false);
            $table->unsignedSmallInteger('maximum_questions')->nullable();
            $table->boolean('fixed_questions')->default(false);
            $table->boolean('fixed_paper_set')->default(false);
            $table->unsignedTinyInteger('paper_sets')->default(1);
            $table->boolean('fix_category_questions')->default(false);
            $table->boolean('fix_category_marks')->default(false);
            $table->string('distribution_type')->nullable(); // mixed | category_wise | equal | weighted | manual
            $table->boolean('fix_marks_each_question')->default(false);

            // Category & distribution JSON
            $table->json('selected_categories')->nullable();
            $table->json('extra_questions_categories')->nullable();
            $table->json('extra_questions_allocations')->nullable();
            $table->json('extra_marks_allocations')->nullable();
            $table->json('question_marks_filter')->nullable();
            $table->json('category_question_rules')->nullable();

            // Shuffle
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_categories')->default(false);
            $table->boolean('shuffle_options')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['exam_id', 'sort_order']);
        });

        Schema::create('exam_part_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_part_id')->constrained('exam_parts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('marks_override')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('updated_by_history')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['exam_part_id', 'question_id']);
            $table->index(['exam_part_id', 'sort_order']);
        });

        Schema::create('exam_part_question_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_part_id')->constrained('exam_parts')->cascadeOnDelete();
            $table->foreignId('question_category_id')->constrained('question_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['exam_part_id', 'question_category_id'], 'exam_part_qc_unique');
        });

        Schema::create('user_app_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('theme', 16)->default('system'); // system | light | dark
            $table->boolean('sidebar_collapsed')->default(false);
            $table->json('preferences')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_app_settings');
        Schema::dropIfExists('exam_part_question_category');
        Schema::dropIfExists('exam_part_question');
        Schema::dropIfExists('exam_parts');
    }
};
