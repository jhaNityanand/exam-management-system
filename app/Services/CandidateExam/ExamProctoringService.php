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

        ExamAttemptEvent::query()->create([
            'exam_attempt_id' => $attempt->id,
            'event' => $event,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);

        $violationTypes = [
            'tab_switch',
            'window_blur',
            'fullscreen_exit',
            'copy_attempt',
            'paste_attempt',
            'right_click',
            'devtools_open',
        ];

        if (! in_array($event, $violationTypes, true)) {
            return ['violation_count' => 0, 'action' => null, 'auto_submitted' => false];
        }

        $count = ExamAttemptViolation::query()
            ->where('exam_attempt_id', $attempt->id)
            ->count() + 1;

        $policy = $attempt->exam?->proctoringPolicy
            ?? $attempt->loadMissing('exam.proctoringPolicy')->exam?->proctoringPolicy;

        $limit = (int) ($policy?->focus_violation_limit ?? 3);
        $action = $policy?->focus_violation_action ?? 'warn';
        $autoSubmit = (bool) ($policy?->auto_submit_on_violation);

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

    public function storeSnapshot(ExamAttempt $attempt, UploadedFile $file, string $type = 'photo'): ExamAttemptSnapshot
    {
        $path = $file->store('exam-snapshots/'.$attempt->id, 'public');

        return ExamAttemptSnapshot::query()->create([
            'exam_attempt_id' => $attempt->id,
            'type' => $type,
            'path' => $path,
            'disk' => 'public',
            'meta' => [
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ],
        ]);
    }
}
