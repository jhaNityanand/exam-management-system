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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExamSessionService
{
    public function __construct(
        protected ExamAttemptService $attemptService,
        protected ExamEligibilityService $eligibility,
        protected ExamRequirementResolver $requirements,
    ) {}

    /**
     * @param  array<string, mixed>  $preferences
     * @param  array<string, mixed>  $deviceMeta
     * @param  array<string, mixed>|null  $policySnapshot
     */
    public function startOrResume(
        Exam $exam,
        User $user,
        array $preferences = [],
        array $deviceMeta = [],
        ?Request $request = null,
        ?array $policySnapshot = null,
        ?string $sessionToken = null
    ): ExamAttempt {
        $this->eligibility->assertCanStart($exam, $user);
        $policy = $this->ensureProctoringPolicy($exam);
        $resolvedPolicy = $policySnapshot ?: $policy->toRuntimeArray();

        $active = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'in_progress'])
            ->latest('id')
            ->first();

        $wasNew = ! $active;

        if ($active && ! empty($resolvedPolicy['enforce_single_session'])) {
            $existingToken = (string) ($active->session_token ?: data_get($active->device_meta, 'session_token'));
            $incoming = (string) ($sessionToken ?: ($deviceMeta['session_token'] ?? ''));
            if ($existingToken !== '' && $incoming !== '' && ! hash_equals($existingToken, $incoming)) {
                throw ValidationException::withMessages([
                    'session' => 'This exam is already active in another browser or device.',
                ]);
            }
        }

        $attempt = $this->attemptService->start($exam, $user);

        $duration = (int) ($exam->duration ?? 0);
        $expiresAt = $attempt->expires_at;
        if (! $expiresAt && $exam->enable_exam_timer && $duration > 0) {
            $expiresAt = ($attempt->started_at ?? now())->copy()->addMinutes($duration);
        }

        $token = $sessionToken
            ?: (string) ($deviceMeta['session_token'] ?? '')
            ?: (string) ($attempt->session_token ?: Str::random(40));

        $attempt->fill([
            'organization_id' => $exam->organization_id,
            'attempt_no' => $attempt->attempt_no ?: 1,
            'status' => 'in_progress',
            'expires_at' => $expiresAt,
            'heartbeat_at' => now(),
            'timezone' => $preferences['timezone'] ?? $exam->timezone ?? config('app.timezone'),
            'exam_config_snapshot' => $attempt->exam_config_snapshot ?: $this->buildConfigSnapshot($exam),
            'preferences_snapshot' => $preferences !== [] ? $preferences : ($attempt->preferences_snapshot ?? []),
            'policy_snapshot' => $attempt->policy_snapshot ?: $resolvedPolicy,
            'device_meta' => $deviceMeta !== [] ? $deviceMeta : ($attempt->device_meta ?? []),
            'session_token' => $attempt->session_token ?: $token,
            'paper_set' => $attempt->paper_set ?: 1,
        ])->save();

        if ($request) {
            try {
                $this->recordDevice($attempt, $request, array_merge($deviceMeta, ['session_token' => $attempt->session_token]));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($wasNew || ! $attempt->events()->where('event', 'session_started')->exists()) {
            ExamAttemptEvent::query()->create([
                'exam_attempt_id' => $attempt->id,
                'event' => 'session_started',
                'payload' => [
                    'policy' => $attempt->policy_snapshot,
                    'device' => $deviceMeta,
                ],
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
        $policy = $attempt->policy_snapshot
            ?: ($exam?->proctoringPolicy?->toRuntimeArray() ?? []);
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
                'session_token' => $attempt->session_token,
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
            'policy' => array_merge([
                'require_webcam' => false,
                'require_microphone' => false,
                'require_fullscreen' => false,
                'require_photo_verification' => false,
                'require_identity_verification' => false,
                'block_copy_paste' => false,
                'block_context_menu' => false,
                'detect_devtools' => false,
                'block_page_refresh' => false,
                'enforce_single_session' => false,
                'single_attempt_per_question' => false,
                'detect_tab_switch' => false,
                'focus_violation_limit' => 3,
                'focus_violation_action' => 'warn',
                'auto_submit_on_violation' => false,
            ], $policy),
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
                'session_token' => isset($deviceMeta['session_token'])
                    ? mb_substr((string) $deviceMeta['session_token'], 0, 64)
                    : $attempt->session_token,
                'meta' => $deviceMeta,
            ]
        );
    }

    protected function ensureProctoringPolicy(Exam $exam): ExamProctoringPolicy
    {
        return $this->requirements->syncPolicy($exam);
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
