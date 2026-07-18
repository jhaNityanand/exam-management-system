<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the exams table (full schema — no follow-up alter migrations required).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('exam_categories')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // Identity
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft | published | active | inactive | suspended
            $table->string('exam_mode')->default('standard'); // standard | practice | proctored
            $table->json('exam_format')->nullable();
            $table->string('difficulty_level')->nullable(); // easy | medium | hard
            $table->string('visibility')->default('public'); // public | private | invite_only
            $table->string('pricing_option')->default('free'); // free | paid | free_for_imported
            $table->string('exam_currency', 10)->nullable();
            $table->decimal('exam_amount', 12, 2)->nullable();
            $table->json('selected_discounts')->nullable();
            $table->json('custom_discounts')->nullable();
            $table->json('tags')->nullable();
            $table->text('instructions')->nullable();
            $table->json('predefined_instruction_rules')->nullable();

            // Timer & Duration
            $table->unsignedSmallInteger('duration')->default(60); // minutes
            $table->boolean('enable_exam_timer')->default(true);
            $table->boolean('auto_submit_on_timer_end')->default(true);

            // Scheduling
            $table->string('schedule_type')->default('any_time'); // any_time | fixed_window
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();

            // Attempts
            $table->string('attempt_limit_type')->default('once'); // once | fixed | unlimited
            $table->unsignedTinyInteger('max_attempts')->default(1);

            // Scoring
            $table->decimal('pass_percentage', 5, 2)->default(50);
            $table->unsignedSmallInteger('total_marks')->nullable();
            $table->unsignedSmallInteger('passing_marks')->nullable();
            $table->decimal('negative_mark_per_question', 8, 4)->default(0);
            $table->boolean('enable_negative_marking')->default(false);
            $table->string('negative_marking_type')->nullable(); // 25 | 33.33 | 50 | 100 (percent)
            $table->boolean('fix_marks_each_question')->default(false);

            // Question Configuration
            $table->unsignedSmallInteger('total_questions')->nullable();
            $table->boolean('use_question_pool')->default(false);
            $table->unsignedSmallInteger('maximum_questions')->nullable();
            $table->boolean('fixed_questions')->default(false); // true = exact selected question set for all candidates
            $table->boolean('fixed_paper_set')->default(false);
            $table->unsignedTinyInteger('paper_sets')->default(1);
            $table->boolean('fix_category_questions')->default(false);
            $table->boolean('fix_category_marks')->default(false);
            $table->string('distribution_type')->nullable(); // mixed | category_wise | equal | weighted | manual
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

            // Candidate Access
            $table->json('imported_candidates')->nullable();
            $table->json('manual_candidate_emails')->nullable();
            $table->json('free_imported_candidates')->nullable();
            $table->json('free_manual_candidate_emails')->nullable();

            // SEO / Metadata (taxonomy/content meta_* convention)
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('slug')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();

            // AI flags
            $table->boolean('ai_generated')->default(false);
            $table->boolean('ai_improve')->default(false);

            // Audit
            $table->json('updated_by_history')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'category_id']);
            $table->index(['organization_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
