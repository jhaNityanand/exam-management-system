<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('category_id')->nullable()->constrained('question_categories')->nullOnDelete();
            $table->unsignedInteger('marks')->default(0);
            $table->json('question_snapshot');
            $table->json('option_order')->nullable();
            $table->json('selection_meta')->nullable();
            $table->timestamps();

            $table->unique(['exam_attempt_id', 'position']);
            $table->index(['exam_attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_questions');
    }
};
