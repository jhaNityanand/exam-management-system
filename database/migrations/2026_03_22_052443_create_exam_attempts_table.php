<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('attempt_no')->default(1);
            $table->string('status')->default('active'); // active | in_progress | submitted | abandoned | expired
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('percentage', 6, 2)->nullable();
            $table->unsignedInteger('correct_count')->nullable();
            $table->unsignedInteger('wrong_count')->nullable();
            $table->unsignedInteger('unanswered_count')->nullable();
            $table->unsignedInteger('time_spent_seconds')->nullable();
            $table->boolean('passed')->nullable();
            $table->json('answers')->nullable();
            $table->json('exam_config_snapshot')->nullable();
            $table->json('preferences_snapshot')->nullable();
            $table->json('policy_snapshot')->nullable();
            $table->json('device_meta')->nullable();
            $table->string('session_token', 64)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('heartbeat_at')->nullable();
            $table->timestamp('last_saved_at')->nullable();
            $table->unsignedInteger('revision')->default(0);
            $table->unsignedSmallInteger('paper_set')->nullable();
            $table->string('timezone', 64)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('submission_reason', 64)->nullable();
            $table->timestamp('result_released_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('updated_by_history')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['exam_id', 'user_id']);
            $table->index(['exam_id', 'status']);
            $table->index(['exam_id', 'user_id', 'status'], 'exam_attempts_exam_user_status_idx');
            $table->unique(['session_token'], 'exam_attempts_session_token_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
