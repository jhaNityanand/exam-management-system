<?php

namespace App\Services\CandidateExam;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptEvent;
use App\Models\ExamAttemptSnapshot;
use App\Models\ExamAttemptViolation;
use App\Models\ExamVerificationChallenge;
use App\Models\User;
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
     * @return array{violation_count:int, action:?string, auto_submitted:bool, submission_reason:?string, submission_message:?string}
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
            'drag_attempt',
            'right_click',
            'devtools_open',
            'page_refresh',
            'navigation_back',
            'session_warning',
            'media_lost',
            'media_grace_expired',
            'keyboard_lock_bypass',
            'mouse_lock_bypass',
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
            return $this->emptyEventResult();
        }

        // Soft / grace events: notify only — never auto-submit from a single accidental click.
        if ($this->isSoftEvent($event)) {
            return [
                'violation_count' => $this->countingViolationCount($attempt),
                'action' => 'warn',
                'auto_submitted' => false,
                'submission_reason' => null,
                'submission_message' => null,
            ];
        }

        if ($this->isDuplicateFocusEvent($attempt, $event)) {
            return [
                'violation_count' => $this->countingViolationCount($attempt),
                'action' => 'deduped',
                'auto_submitted' => false,
                'submission_reason' => null,
                'submission_message' => null,
            ];
        }

        $forceSubmit = $this->forcesAutoSubmit($event);
        $count = $this->countingViolationCount($attempt) + 1;
        $limit = max(1, (int) ($policy['focus_violation_limit'] ?? 3));
        $action = $policy['focus_violation_action'] ?? 'warn';
        $autoSubmit = (bool) ($policy['auto_submit_on_violation'] ?? false);

        $applied = 'warn';
        if ($forceSubmit || $count >= $limit) {
            $applied = ($forceSubmit || $autoSubmit || $action === 'auto_submit')
                ? 'auto_submit'
                : ($action ?: 'flag');
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
        $submissionReason = null;
        $submissionMessage = null;
        if ($applied === 'auto_submit') {
            $submissionReason = $this->resolveViolationSubmissionReason($attempt, $event, $payload);
            $this->grading->submit($attempt, reason: $submissionReason, auto: true);
            $autoSubmitted = true;
            $submissionMessage = ExamAttempt::labelForSubmissionReason($submissionReason);
        }

        return [
            'violation_count' => $count,
            'action' => $applied,
            'auto_submitted' => $autoSubmitted,
            'submission_reason' => $submissionReason,
            'submission_message' => $submissionMessage,
        ];
    }

    /**
     * @return array{violation_count:int, action:?string, auto_submitted:bool, submission_reason:?string, submission_message:?string}
     */
    protected function emptyEventResult(): array
    {
        return [
            'violation_count' => 0,
            'action' => null,
            'auto_submitted' => false,
            'submission_reason' => null,
            'submission_message' => null,
        ];
    }

    protected function isSoftEvent(string $event): bool
    {
        // Soft events are blocked/logged client-side but must not burn the shared violation budget.
        return in_array($event, [
            'right_click',
            'media_lost',
            'session_warning',
            'keyboard_lock_bypass',
            'mouse_lock_bypass',
        ], true);
    }

    protected function forcesAutoSubmit(string $event): bool
    {
        return $event === 'media_grace_expired';
    }

    protected function countingViolationCount(ExamAttempt $attempt): int
    {
        return ExamAttemptViolation::query()
            ->where('exam_attempt_id', $attempt->id)
            ->whereNotIn('type', [
                'right_click',
                'media_lost',
                'session_warning',
                'keyboard_lock_bypass',
                'mouse_lock_bypass',
            ])
            ->count();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveViolationSubmissionReason(ExamAttempt $attempt, string $event, array $payload = []): string
    {
        if ($event === 'media_grace_expired' || $event === 'media_lost') {
            $detail = strtolower((string) ($payload['reason'] ?? $payload['kind'] ?? ''));
            if (str_contains($detail, 'audio') || str_contains($detail, 'mic')) {
                return 'violation_microphone_disabled';
            }
            if (str_contains($detail, 'video') || str_contains($detail, 'camera')) {
                return 'violation_camera_disabled';
            }

            return 'violation_media_lost';
        }

        $types = ExamAttemptViolation::query()
            ->where('exam_attempt_id', $attempt->id)
            ->whereNotIn('type', [
                'right_click',
                'media_lost',
                'session_warning',
                'keyboard_lock_bypass',
                'mouse_lock_bypass',
            ])
            ->pluck('type')
            ->unique()
            ->values();

        if ($types->count() > 1) {
            return 'violation_multiple';
        }

        if (in_array($event, ['copy_attempt', 'paste_attempt', 'cut_attempt', 'drag_attempt'], true)) {
            return 'violation_copy_paste';
        }

        if (in_array($event, ['tab_switch', 'window_blur'], true)) {
            return 'violation_tab_switch';
        }

        return 'violation_'.$event;
    }

    public function storeSnapshot(ExamAttempt $attempt, UploadedFile $file, string $type = 'selfie'): ExamAttemptSnapshot
    {
        if ($type === 'selfie') {
            $this->deleteAttemptSelfies($attempt);
        }

        $candidateId = (int) $attempt->user_id;
        $directory = 'exam-snapshots/'.$attempt->id.'/candidate-'.$candidateId;
        $path = $file->storeAs($directory, 'selfie-'.now()->format('YmdHis').'.'.$file->getClientOriginalExtension(), 'local');

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
                'user_id' => $candidateId,
                'candidate_id' => $candidateId,
            ],
        ]);
    }

    public function storeChallengeSelfie(
        ExamVerificationChallenge $challenge,
        UploadedFile $file,
        Exam $exam,
        User $user
    ): void {
        $this->clearChallengeSelfie($challenge);

        $directory = 'exam-verification/'.$exam->id.'/candidate-'.$user->id;
        $path = $file->storeAs($directory, 'selfie.jpg', 'local');

        $challenge->selfie_path = $path;
        $challenge->selfie_disk = 'local';
        $challenge->save();
    }

    public function clearChallengeSelfie(ExamVerificationChallenge $challenge): void
    {
        if ($challenge->selfie_path) {
            Storage::disk($challenge->selfie_disk ?: 'local')->delete($challenge->selfie_path);
        }

        $challenge->selfie_path = null;
        $challenge->selfie_disk = null;
    }

    public function attachVerificationSelfie(
        ExamAttempt $attempt,
        string $path,
        string $disk,
        ?string $challengeToken = null
    ): ExamAttemptSnapshot {
        $this->deleteAttemptSelfies($attempt);

        $candidateId = (int) $attempt->user_id;
        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
        $target = 'exam-snapshots/'.$attempt->id.'/candidate-'.$candidateId.'/selfie.'.$extension;
        $storage = Storage::disk($disk);

        if ($path !== $target) {
            if ($storage->exists($target)) {
                $storage->delete($target);
            }
            $storage->copy($path, $target);
        }

        return ExamAttemptSnapshot::query()->create([
            'exam_attempt_id' => $attempt->id,
            'type' => 'selfie',
            'path' => $target,
            'disk' => $disk,
            'verification_status' => 'captured',
            'challenge_token' => $challengeToken,
            'meta' => [
                'source' => 'prepare_verification',
                'user_id' => $candidateId,
                'candidate_id' => $candidateId,
                'exam_id' => (int) $attempt->exam_id,
            ],
        ]);
    }

    protected function deleteAttemptSelfies(ExamAttempt $attempt): void
    {
        $existing = ExamAttemptSnapshot::query()
            ->where('exam_attempt_id', $attempt->id)
            ->where('type', 'selfie')
            ->get();

        foreach ($existing as $snapshot) {
            if ($snapshot->path) {
                Storage::disk($snapshot->disk ?: 'local')->delete($snapshot->path);
            }
            $snapshot->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $policy
     */
    protected function eventApplies(string $event, array $policy): bool
    {
        return match ($event) {
            'tab_switch', 'window_blur' => (bool) ($policy['detect_tab_switch'] ?? false),
            'fullscreen_exit' => (bool) ($policy['require_fullscreen'] ?? false),
            'copy_attempt', 'paste_attempt', 'cut_attempt', 'drag_attempt' => (bool) ($policy['block_copy_paste'] ?? false),
            'right_click' => (bool) ($policy['block_context_menu'] ?? false),
            'devtools_open' => (bool) ($policy['detect_devtools'] ?? false),
            'page_refresh', 'navigation_back' => (bool) ($policy['block_page_refresh'] ?? false),
            'media_lost', 'media_grace_expired' => (bool) (($policy['require_webcam'] ?? false) || ($policy['require_microphone'] ?? false)),
            'keyboard_lock_bypass' => (bool) ($policy['lock_keyboard'] ?? false),
            'mouse_lock_bypass' => (bool) ($policy['lock_mouse'] ?? false),
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
