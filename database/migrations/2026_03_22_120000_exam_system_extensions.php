<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supplementary tables that depend on core exams / questions / users:
 *  - exam_question (exam ↔ question pivot)
 *  - exam_question_category (exam ↔ question-category selection)
 *  - user_app_settings (per-user UI preferences)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('marks_override')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('updated_by_history')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['exam_id', 'question_id']);
            $table->index(['exam_id', 'sort_order']);
        });

        Schema::create('exam_question_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('question_category_id')->constrained('question_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['exam_id', 'question_category_id']);
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
        Schema::dropIfExists('exam_question_category');
        Schema::dropIfExists('exam_question');
    }
};
