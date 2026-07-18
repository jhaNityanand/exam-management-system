<?php

namespace App\Services\CandidateExam;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptDevice;
use App\Models\ExamAttemptEvent;
use App\Models\ExamProctoringPolicy;
use App\Models\User;
use App\Services\ExamAttemptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamSessionService
{
    public function __construct(
        protected ExamAttemptService $attemptService,
        protected ExamEligibilityService $eligibility,
    ) {}

    /**
     * @param  array<string, mixed>  $preferences
     * @param  array<string, mixed>  $deviceMeta
     */
    public function startOrResume(Exam $exam, User $user, array $preferences = [], array $deviceMeta = [], ?Request $request = null): ExamAttempt
    {
        $this->eligibility->assertCanStart($exam, $user);
        $this->ensureProctoringPolicy($exam);

        $wasNew = ! ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'in_progress'])
            ->exists();

        $attempt = $this->attemptService->start($exam, $user);

        $duration = (int) ($exam->duration ?? 0);
        $expiresAt = $attempt->expires_at;
        if (! $expiresAt && $exam->enable_exam_timer && $duration > 0) {
            $expiresAt = ($attempt->started_at ?? now())->copy()->addMinutes($duration);
        }

        $attempt->fill([
            'organization_id' => $exam->organization_id,
            'attempt_no' => $attempt->attempt_no ?: 1,
            'status' => 'in_progress',
            'expires_at' => $expiresAt,
            'heartbeat_at' => now(),
            'timezone' => $preferences['timezone'] ?? $exam->timezone ?? config('app.timezone'),
            'exam_config_snapshot' => $attempt->exam_config_snapshot ?: $this->buildConfigSnapshot($exam),
            'preferences_snapshot' => $preferences !== [] ? $preferences : ($attempt->preferences_snapshot ?? []),
            'device_meta' => $deviceMeta !== [] ? $deviceMeta : ($attempt->device_meta ?? []),
            'paper_set' => $attempt->paper_set ?: 1,
        ])->save();

        if ($request) {
            try {
                $this->recordDevice($attempt, $request, $deviceMeta);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($wasNew || ! $attempt->events()->where('event', 'session_started')->exists()) {
            ExamAttemptEvent::query()->create([
                'exam_attempt_id' => $attempt->id,
                'event' => 'session_started',
                'payload' => ['preferences' => $preferences],
                'occurred_at' => now(),
            ]);
        }

        return $attempt;
    }

    public function heartbeat(ExamAttempt $attempt): ExamAttempt
    {
        $this->expireIfNeeded($attempt);
        $attempt->heartbeat_at = now();
        $attempt->save();

        return $attempt;
    }

    public function expireIfNeeded(ExamAttempt $attempt): ExamAttempt
    {
        if (! in_array($attempt->status, ['active', 'in_progress'], true)) {
            return $attempt;
        }

        if ($attempt->expires_at && now()->greaterThan($attempt->expires_at)) {
            app(ExamGradingService::class)->submit($attempt, reason: 'timer_expired', auto: true);
            $attempt->refresh();
        }

        return $attempt;
    }

    /**
     * @return array<string, mixed>
     */
    public function toRuntimePayload(ExamAttempt $attempt): array
    {
        $attempt->loadMissing(['attemptQuestions', 'exam.proctoringPolicy', 'attemptAnswers', 'exam']);
        $exam = $attempt->exam;
        $policy = $exam?->proctoringPolicy;
        $answers = $attempt->attemptAnswers->keyBy('exam_attempt_question_id');

        $questions = $attempt->attemptQuestions->sortBy('position')->values()->map(function ($row) use ($answers) {
            $payload = $row->toCandidatePayload();
            $answer = $answers->get($row->id);
            $payload['answer'] = $answer?->answer_value;
            $payload['is_marked_for_review'] = (bool) ($answer?->is_marked_for_review);
            $payload['is_visited'] = (bool) ($answer?->is_visited);
            $payload['is_answered'] = (bool) ($answer?->is_answered);

            return $payload;
        })->all();

        return [
            'server_now' => now()->toIso8601String(),
            'attempt' => [
                'id' => $attempt->id,
                'status' => $attempt->status,
                'attempt_no' => $attempt->attempt_no,
                'started_at' => optional($attempt->started_at)?->toIso8601String(),
                'expires_at' => optional($attempt->expires_at)?->toIso8601String(),
                'revision' => $attempt->revision,
                'preferences' => $attempt->preferences_snapshot ?? [],
            ],
            'exam' => [
                'id' => $exam?->id,
                'title' => $exam?->title,
                'slug' => $exam?->slug,
                'duration' => $exam?->duration,
                'enable_exam_timer' => (bool) $exam?->enable_exam_timer,
                'auto_submit_on_timer_end' => (bool) $exam?->auto_submit_on_timer_end,
                'total_questions' => $exam?->total_questions,
                'total_marks' => $exam?->total_marks,
                'passing_marks' => $exam?->passing_marks,
                'negative_marking' => [
                    'enabled' => (bool) $exam?->enable_negative_marking,
                    'type' => $exam?->negative_marking_type,
                    'per_question' => $exam?->negative_mark_per_question,
                ],
            ],
            'policy' => [
                'require_webcam' => (bool) $policy?->require_webcam,
                'require_microphone' => (bool) $policy?->require_microphone,
                'require_fullscreen' => (bool) $policy?->require_fullscreen,
                'block_copy_paste' => (bool) $policy?->block_copy_paste,
                'detect_tab_switch' => (bool) ($policy?->detect_tab_switch ?? true),
                'focus_violation_limit' => (int) ($policy?->focus_violation_limit ?? 3),
                'focus_violation_action' => $policy?->focus_violation_action ?? 'warn',
                'auto_submit_on_violation' => (bool) $policy?->auto_submit_on_violation,
            ],
            'questions' => $questions,
        ];
    }

    /**
     * @param  array<string, mixed>  $deviceMeta
     */
    protected function recordDevice(ExamAttempt $attempt, Request $request, array $deviceMeta): void
    {
        $browser = (string) ($deviceMeta['browser'] ?? $request->userAgent() ?? '');
        $browser = mb_substr($browser, 0, 120);

        ExamAttemptDevice::query()->updateOrCreate(
            ['exam_attempt_id' => $attempt->id],
            [
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 512),
                'browser' => $browser !== '' ? $browser : null,
                'device_type' => isset($deviceMeta['device_type'])
                    ? mb_substr((string) $deviceMeta['device_type'], 0, 64)
                    : null,
                'os' => isset($deviceMeta['os'])
                    ? mb_substr((string) $deviceMeta['os'], 0, 120)
                    : null,
                'screen_resolution' => isset($deviceMeta['screen_resolution'])
                    ? mb_substr((string) $deviceMeta['screen_resolution'], 0, 32)
                    : null,
                'timezone' => isset($deviceMeta['timezone'])
                    ? mb_substr((string) $deviceMeta['timezone'], 0, 64)
                    : null,
                'meta' => $deviceMeta,
            ]
        );
    }

    protected function ensureProctoringPolicy(Exam $exam): ExamProctoringPolicy
    {
        return ExamProctoringPolicy::query()->firstOrCreate(
            ['exam_id' => $exam->id],
            [
                'require_webcam' => $exam->exam_mode === 'proctored',
                'require_microphone' => $exam->exam_mode === 'proctored',
                'require_fullscreen' => in_array('fullscreen_required', $exam->predefined_instruction_rules ?? [], true)
                    || $exam->exam_mode === 'proctored',
                'require_photo_verification' => in_array('id_verification_required', $exam->predefined_instruction_rules ?? [], true),
                'detect_tab_switch' => true,
                'focus_violation_limit' => 3,
                'focus_violation_action' => in_array('tab_switch_autosubmit', $exam->predefined_instruction_rules ?? [], true)
                    ? 'auto_submit'
                    : 'warn',
                'auto_submit_on_violation' => in_array('tab_switch_autosubmit', $exam->predefined_instruction_rules ?? [], true),
                'block_copy_paste' => in_array('disable_copy_paste', $exam->predefined_instruction_rules ?? [], true),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildConfigSnapshot(Exam $exam): array
    {
        return [
            'duration' => $exam->duration,
            'enable_exam_timer' => $exam->enable_exam_timer,
            'auto_submit_on_timer_end' => $exam->auto_submit_on_timer_end,
            'total_questions' => $exam->total_questions,
            'total_marks' => $exam->total_marks,
            'passing_marks' => $exam->passing_marks,
            'pass_percentage' => $exam->pass_percentage,
            'enable_negative_marking' => $exam->enable_negative_marking,
            'negative_marking_type' => $exam->negative_marking_type,
            'negative_mark_per_question' => $exam->negative_mark_per_question,
            'result_release_mode' => $exam->result_release_mode ?? 'immediate',
            'result_release_at' => optional($exam->result_release_at)?->toIso8601String(),
            'shuffle_questions' => $exam->shuffle_questions,
            'shuffle_options' => $exam->shuffle_options,
            'exam_format' => $exam->exam_format,
            'instructions' => $exam->instructions,
            'predefined_instruction_rules' => $exam->predefined_instruction_rules,
            'timezone' => $exam->timezone ?? config('app.timezone'),
            'language' => $exam->language ?? 'en',
        ];
    }
}
