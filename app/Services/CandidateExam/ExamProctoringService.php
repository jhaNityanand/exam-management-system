<?php

namespace App\Services\CandidateExam;

use App\Models\ExamAttempt;
use App\Models\ExamAttemptEvent;
use App\Models\ExamAttemptSnapshot;
use App\Models\ExamAttemptViolation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ExamProctoringService
{
    public function __construct(
        protected ExamSessionService $sessions,
        protected ExamGradingService $grading,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{violation_count:int, action:?string, auto_submitted:bool}
     */
    public function recordEvent(ExamAttempt $attempt, string $event, array $payload = []): array
    {
        $attempt = $this->sessions->expireIfNeeded($attempt);
        if (! in_array($attempt->status, ['active', 'in_progress'], true)) {
            throw ValidationException::withMessages([
                'attempt' => 'This attempt is no longer active.',
            ]);
        }

        $allowed = [
            'tab_switch',
            'window_blur',
            'fullscreen_exit',
            'copy_attempt',
            'paste_attempt',
            'cut_attempt',
            'right_click',
            'devtools_open',
            'page_refresh',
            'session_warning',
            'media_lost',
        ];

        if (! in_array($event, $allowed, true)) {
            throw ValidationException::withMessages([
                'event' => 'Unsupported proctoring event.',
            ]);
        }

        ExamAttemptEvent::query()->create([
            'exam_attempt_id' => $attempt->id,
            'event' => $event,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);

        $policy = $attempt->policy_snapshot
            ?: ($attempt->loadMissing('exam.proctoringPolicy')->exam?->proctoringPolicy?->toRuntimeArray() ?? []);

        if (! $this->eventApplies($event, $policy)) {
            return ['violation_count' => 0, 'action' => null, 'auto_submitted' => false];
        }

        if ($this->isDuplicateFocusEvent($attempt, $event)) {
            return ['violation_count' => 0, 'action' => 'deduped', 'auto_submitted' => false];
        }

        $count = ExamAttemptViolation::query()
            ->where('exam_attempt_id', $attempt->id)
            ->count() + 1;

        $limit = (int) ($policy['focus_violation_limit'] ?? 3);
        $action = $policy['focus_violation_action'] ?? 'warn';
        $autoSubmit = (bool) ($policy['auto_submit_on_violation'] ?? false);

        $applied = 'warn';
        if ($count >= $limit) {
            $applied = $autoSubmit || $action === 'auto_submit' ? 'auto_submit' : ($action ?: 'flag');
        }

        ExamAttemptViolation::query()->create([
            'exam_attempt_id' => $attempt->id,
            'type' => $event,
            'sequence' => $count,
            'action_taken' => $applied,
            'meta' => $payload,
            'occurred_at' => now(),
        ]);

        $autoSubmitted = false;
        if ($applied === 'auto_submit') {
            $this->grading->submit($attempt, reason: 'violation_limit', auto: true);
            $autoSubmitted = true;
        }

        return [
            'violation_count' => $count,
            'action' => $applied,
            'auto_submitted' => $autoSubmitted,
        ];
    }

    public function storeSnapshot(ExamAttempt $attempt, UploadedFile $file, string $type = 'selfie'): ExamAttemptSnapshot
    {
        $path = $file->store('exam-snapshots/'.$attempt->id, 'local');

        return ExamAttemptSnapshot::query()->create([
            'exam_attempt_id' => $attempt->id,
            'type' => $type,
            'path' => $path,
            'disk' => 'local',
            'verification_status' => 'captured',
            'meta' => [
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ],
        ]);
    }

    public function attachVerificationSelfie(
        ExamAttempt $attempt,
        string $path,
        string $disk,
        ?string $challengeToken = null
    ): ExamAttemptSnapshot {
        $target = 'exam-snapshots/'.$attempt->id.'/'.basename($path);
        if ($path !== $target) {
            Storage::disk($disk)->copy($path, $target);
        }

        return ExamAttemptSnapshot::query()->create([
            'exam_attempt_id' => $attempt->id,
            'type' => 'selfie',
            'path' => $target,
            'disk' => $disk,
            'verification_status' => 'captured',
            'challenge_token' => $challengeToken,
            'meta' => ['source' => 'prepare_verification'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $policy
     */
    protected function eventApplies(string $event, array $policy): bool
    {
        return match ($event) {
            'tab_switch', 'window_blur' => (bool) ($policy['detect_tab_switch'] ?? false),
            'fullscreen_exit' => (bool) ($policy['require_fullscreen'] ?? false),
            'copy_attempt', 'paste_attempt', 'cut_attempt' => (bool) ($policy['block_copy_paste'] ?? false),
            'right_click' => (bool) ($policy['block_context_menu'] ?? false),
            'devtools_open' => (bool) ($policy['detect_devtools'] ?? false),
            'page_refresh' => (bool) ($policy['block_page_refresh'] ?? false),
            'media_lost' => (bool) (($policy['require_webcam'] ?? false) || ($policy['require_microphone'] ?? false)),
            default => true,
        };
    }

    protected function isDuplicateFocusEvent(ExamAttempt $attempt, string $event): bool
    {
        if (! in_array($event, ['tab_switch', 'window_blur'], true)) {
            return false;
        }

        $recent = ExamAttemptViolation::query()
            ->where('exam_attempt_id', $attempt->id)
            ->whereIn('type', ['tab_switch', 'window_blur'])
            ->where('occurred_at', '>=', now()->subSeconds(2))
            ->exists();

        return $recent;
    }
}
