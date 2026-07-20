<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Candidate exam runtime child tables and proctoring policy.
 * Exam/attempt base columns live in their original create migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_proctoring_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->unique()->constrained('exams')->cascadeOnDelete();
            $table->boolean('require_webcam')->default(false);
            $table->boolean('require_microphone')->default(false);
            $table->boolean('require_fullscreen')->default(false);
            $table->boolean('require_photo_verification')->default(false);
            $table->boolean('require_identity_verification')->default(false);
            $table->boolean('block_copy_paste')->default(false);
            $table->boolean('block_context_menu')->default(false);
            $table->boolean('detect_devtools')->default(false);
            $table->boolean('block_page_refresh')->default(false);
            $table->boolean('enforce_single_session')->default(false);
            $table->boolean('single_attempt_per_question')->default(false);
            $table->boolean('detect_tab_switch')->default(true);
            $table->unsignedSmallInteger('focus_violation_limit')->default(3);
            $table->string('focus_violation_action', 32)->default('warn'); // warn | auto_submit | flag
            $table->boolean('auto_submit_on_violation')->default(false);
            $table->json('enabled_rule_keys')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('exam_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignId('exam_attempt_question_id')->constrained('exam_attempt_questions')->cascadeOnDelete();
            $table->json('answer_value')->nullable();
            $table->boolean('is_marked_for_review')->default(false);
            $table->boolean('is_visited')->default(false);
            $table->boolean('is_answered')->default(false);
            $table->timestamp('answered_at')->nullable();
            $table->unsignedInteger('revision')->default(0);
            $table->decimal('awarded_marks', 8, 2)->nullable();
            $table->boolean('is_correct')->nullable();
            $table->string('grading_status', 32)->default('pending');
            $table->timestamps();

            $table->unique(['exam_attempt_id', 'exam_attempt_question_id'], 'attempt_answer_unique');
        });

        Schema::create('exam_attempt_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->string('type', 64);
            $table->unsignedInteger('sequence')->default(1);
            $table->string('action_taken', 32)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['exam_attempt_id', 'type']);
        });

        Schema::create('exam_attempt_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->string('event', 64);
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['exam_attempt_id', 'event']);
        });

        Schema::create('exam_attempt_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->unique()->constrained('exam_attempts')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('browser', 120)->nullable();
            $table->string('device_type', 64)->nullable();
            $table->string('os', 120)->nullable();
            $table->string('screen_resolution', 32)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->string('session_token', 64)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('exam_attempt_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->string('type', 32)->default('selfie'); // selfie | webcam | identity
            $table->string('path');
            $table->string('disk', 32)->default('local');
            $table->string('verification_status', 32)->default('captured'); // captured | accepted | rejected
            $table->string('challenge_token', 64)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['exam_attempt_id', 'type']);
        });

        Schema::create('exam_verification_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->json('required_checks')->nullable();
            $table->json('completed_checks')->nullable();
            $table->string('selfie_path')->nullable();
            $table->string('selfie_disk', 32)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['exam_id', 'user_id', 'expires_at'], 'exam_verify_challenge_lookup_idx');
        });

        Schema::create('exam_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('source', 32)->default('manual');
            $table->string('status', 32)->default('active');
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['exam_id', 'user_id', 'status'], 'exam_entitlement_user_status_idx');
        });

        Schema::create('exam_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('entitlement_id')->nullable()->constrained('exam_entitlements')->nullOnDelete();
            $table->string('provider', 32)->default('placeholder');
            $table->string('status', 32)->default('pending');
            $table->string('currency', 8)->default('INR');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('reference', 120)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['exam_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_payments');
        Schema::dropIfExists('exam_entitlements');
        Schema::dropIfExists('exam_verification_challenges');
        Schema::dropIfExists('exam_attempt_snapshots');
        Schema::dropIfExists('exam_attempt_devices');
        Schema::dropIfExists('exam_attempt_events');
        Schema::dropIfExists('exam_attempt_violations');
        Schema::dropIfExists('exam_attempt_answers');
        Schema::dropIfExists('exam_proctoring_policies');
    }
};
