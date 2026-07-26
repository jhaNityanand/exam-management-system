<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Centralized polymorphic feedback (exams first; blogs/news/courses later).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Convenience FKs for exam-flow queries (optional; morph is source of truth).
            $table->foreignId('exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->foreignId('exam_attempt_id')->nullable()->constrained('exam_attempts')->nullOnDelete();

            $table->string('feedbackable_type');
            $table->unsignedBigInteger('feedbackable_id');

            $table->unsignedTinyInteger('rating'); // 1–5
            $table->string('title', 160)->nullable();
            $table->text('message');

            // active | inactive | pending | spam | archived
            $table->string('status', 20)->default('pending');
            $table->boolean('is_public')->default(false);

            $table->string('source', 40)->nullable(); // result_modal | exam_show | api | admin
            $table->string('locale', 16)->nullable();
            $table->json('meta')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('updated_by_history')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['feedbackable_type', 'feedbackable_id'], 'feedback_feedbackable_idx');
            $table->index(['exam_id', 'status', 'is_public'], 'feedback_exam_public_idx');
            $table->index(['organization_id', 'status'], 'feedback_org_status_idx');
            $table->index(['user_id', 'feedbackable_type', 'feedbackable_id'], 'feedback_user_target_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
