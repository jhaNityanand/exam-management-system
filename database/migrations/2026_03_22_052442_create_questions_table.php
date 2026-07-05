<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('question_categories')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // Content
            $table->text('body');
            $table->string('type')->default('mcq');          // mcq | true_false | short_answer
            $table->boolean('allows_multiple')->default(false);
            $table->json('options')->nullable();              // MCQ options array
            $table->string('correct_answer')->nullable();
            $table->json('correct_answers')->nullable();      // for multi-select MCQ
            $table->text('explanation')->nullable();
            $table->string('reference')->nullable();          // e.g. "UPSC Prelims 2023"

            // Scoring & Classification
            $table->string('marks_type')->default('single');
            $table->json('marks_list')->nullable();
            $table->unsignedTinyInteger('marks')->default(1);
            $table->string('difficulty')->default('medium'); // easy | medium | hard
            $table->string('status')->default('active');     // active | inactive | suspended

            // SEO / Metadata
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('slug')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->boolean('ai_generated')->default(false);
            $table->boolean('ai_improve')->default(false);

            // Audit
            $table->json('updated_by_history')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
