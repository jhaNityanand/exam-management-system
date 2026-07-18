<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Candidate exam runtime: public metadata, proctoring policy, attempt lifecycle,
 * answers, violations, devices, snapshots, and payment entitlements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'banner_image_id')) {
                $table->foreignId('banner_image_id')->nullable()->after('og_image_id')->constrained('galleries')->nullOnDelete();
            }
            if (! Schema::hasColumn('exams', 'language')) {
                $table->string('language', 16)->default('en')->after('visibility');
            }
            if (! Schema::hasColumn('exams', 'timezone')) {
                $table->string('timezone', 64)->default('UTC')->after('language');
            }
            if (! Schema::hasColumn('exams', 'registration_deadline')) {
                $table->timestamp('registration_deadline')->nullable()->after('scheduled_end');
            }
            if (! Schema::hasColumn('exams', 'result_release_mode')) {
                $table->string('result_release_mode', 32)->default('immediate')->after('passing_marks');
            }
            if (! Schema::hasColumn('exams', 'result_release_at')) {
                $table->timestamp('result_release_at')->nullable()->after('result_release_mode');
            }
            if (! Schema::hasColumn('exams', 'demo_enabled')) {
                $table->boolean('demo_enabled')->default(false)->after('visibility');
            }
        });

        Schema::create('exam_proctoring_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->unique()->constrained('exams')->cascadeOnDelete();
            $table->boolean('require_webcam')->default(false);
            $table->boolean('require_microphone')->default(false);
            $table->boolean('require_fullscreen')->default(false);
            $table->boolean('require_photo_verification')->default(false);
            $table->boolean('require_identity_verification')->default(false);
            $table->boolean('block_copy_paste')->default(false);
            $table->boolean('detect_tab_switch')->default(true);
            $table->unsignedSmallInteger('focus_violation_limit')->default(3);
            $table->string('focus_violation_action', 32)->default('warn'); // warn | auto_submit | flag
            $table->boolean('auto_submit_on_violation')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_attempts', 'organization_id')) {
                $table->foreignId('organization_id')->nullable()->after('exam_id')->constrained('organizations')->nullOnDelete();
            }
            if (! Schema::hasColumn('exam_attempts', 'attempt_no')) {
                $table->unsignedInteger('attempt_no')->default(1)->after('user_id');
            }
            if (! Schema::hasColumn('exam_attempts', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('started_at');
            }
            if (! Schema::hasColumn('exam_attempts', 'heartbeat_at')) {
                $table->timestamp('heartbeat_at')->nullable()->after('expires_at');
            }
            if (! Schema::hasColumn('exam_attempts', 'last_saved_at')) {
                $table->timestamp('last_saved_at')->nullable()->after('heartbeat_at');
            }
            if (! Schema::hasColumn('exam_attempts', 'revision')) {
                $table->unsignedInteger('revision')->default(0)->after('last_saved_at');
            }
            if (! Schema::hasColumn('exam_attempts', 'paper_set')) {
                $table->unsignedSmallInteger('paper_set')->nullable()->after('revision');
            }
            if (! Schema::hasColumn('exam_attempts', 'timezone')) {
                $table->string('timezone', 64)->nullable()->after('paper_set');
            }
            if (! Schema::hasColumn('exam_attempts', 'exam_config_snapshot')) {
                $table->json('exam_config_snapshot')->nullable()->after('answers');
            }
            if (! Schema::hasColumn('exam_attempts', 'preferences_snapshot')) {
                $table->json('preferences_snapshot')->nullable()->after('exam_config_snapshot');
            }
            if (! Schema::hasColumn('exam_attempts', 'device_meta')) {
                $table->json('device_meta')->nullable()->after('preferences_snapshot');
            }
            if (! Schema::hasColumn('exam_attempts', 'submission_reason')) {
                $table->string('submission_reason', 64)->nullable()->after('submitted_at');
            }
            if (! Schema::hasColumn('exam_attempts', 'result_released_at')) {
                $table->timestamp('result_released_at')->nullable()->after('submission_reason');
            }
            if (! Schema::hasColumn('exam_attempts', 'percentage')) {
                $table->decimal('percentage', 6, 2)->nullable()->after('score');
            }
            if (! Schema::hasColumn('exam_attempts', 'correct_count')) {
                $table->unsignedInteger('correct_count')->nullable()->after('percentage');
            }
            if (! Schema::hasColumn('exam_attempts', 'wrong_count')) {
                $table->unsignedInteger('wrong_count')->nullable()->after('correct_count');
            }
            if (! Schema::hasColumn('exam_attempts', 'unanswered_count')) {
                $table->unsignedInteger('unanswered_count')->nullable()->after('wrong_count');
            }
            if (! Schema::hasColumn('exam_attempts', 'time_spent_seconds')) {
                $table->unsignedInteger('time_spent_seconds')->nullable()->after('unanswered_count');
            }
            $table->index(['exam_id', 'user_id', 'status'], 'exam_attempts_exam_user_status_idx');
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
            $table->string('grading_status', 32)->default('pending'); // pending | auto | manual | skipped
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
            $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('browser', 120)->nullable();
            $table->string('device_type', 64)->nullable();
            $table->string('os', 120)->nullable();
            $table->string('screen_resolution', 32)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('exam_attempt_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->string('type', 32)->default('photo'); // photo | webcam
            $table->string('path');
            $table->string('disk', 32)->default('public');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('exam_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('source', 32)->default('manual'); // manual | payment | invite | free
            $table->string('status', 32)->default('active'); // active | revoked | expired
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
            $table->string('status', 32)->default('pending'); // pending | paid | failed | refunded
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
        Schema::dropIfExists('exam_attempt_snapshots');
        Schema::dropIfExists('exam_attempt_devices');
        Schema::dropIfExists('exam_attempt_events');
        Schema::dropIfExists('exam_attempt_violations');
        Schema::dropIfExists('exam_attempt_answers');

        Schema::table('exam_attempts', function (Blueprint $table) {
            foreach ([
                'organization_id', 'attempt_no', 'expires_at', 'heartbeat_at', 'last_saved_at', 'revision',
                'paper_set', 'timezone', 'exam_config_snapshot', 'preferences_snapshot', 'device_meta',
                'submission_reason', 'result_released_at', 'percentage', 'correct_count', 'wrong_count',
                'unanswered_count', 'time_spent_seconds',
            ] as $column) {
                if (Schema::hasColumn('exam_attempts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('exam_proctoring_policies');

        Schema::table('exams', function (Blueprint $table) {
            foreach (['banner_image_id', 'language', 'timezone', 'registration_deadline', 'result_release_mode', 'result_release_at', 'demo_enabled'] as $column) {
                if (Schema::hasColumn('exams', $column)) {
                    if ($column === 'banner_image_id') {
                        $table->dropConstrainedForeignId('banner_image_id');
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });
    }
};
