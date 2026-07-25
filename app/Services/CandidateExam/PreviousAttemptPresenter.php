<?php

namespace App\Services\CandidateExam;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Support\Collection;

class PreviousAttemptPresenter
{
    public function __construct(
        protected ExamGradingService $grading
    ) {}

    /**
     * @param  Collection<int, ExamAttempt>  $attempts
     * @return list<array<string, mixed>>
     */
    public function presentMany(Collection $attempts, Exam $exam): array
    {
        return $attempts
            ->values()
            ->map(fn (ExamAttempt $attempt, int $index) => $this->present($attempt, $exam, $index === 0))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function present(ExamAttempt $attempt, Exam $exam, bool $isLatest = false): array
    {
        $config = is_array($attempt->exam_config_snapshot) ? $attempt->exam_config_snapshot : [];
        $timezone = $this->resolveTimezone($attempt, $exam);
        $startedAt = $attempt->started_at;
        $endedAt = $attempt->submitted_at;
        $timeSpentSeconds = $this->resolveTimeSpentSeconds($attempt);
        $totalQuestions = (int) ($config['total_questions'] ?? $exam->total_questions ?? 0);
        if ($totalQuestions <= 0) {
            $totalQuestions = max(
                0,
                (int) ($attempt->correct_count ?? 0)
                + (int) ($attempt->wrong_count ?? 0)
                + (int) ($attempt->unanswered_count ?? 0)
            );
        }

        $correct = (int) ($attempt->correct_count ?? 0);
        $incorrect = (int) ($attempt->wrong_count ?? 0);
        $unanswered = (int) ($attempt->unanswered_count ?? max(0, $totalQuestions - $correct - $incorrect));
        $attempted = max(0, $totalQuestions - $unanswered);
        $markedForReview = (int) ($attempt->marked_for_review_count ?? 0);
        $totalMarks = (float) ($config['total_marks'] ?? $exam->total_marks ?? 0);
        $passingMarks = (float) ($config['passing_marks'] ?? $exam->passing_marks ?? 0);
        $score = $attempt->score !== null ? (float) $attempt->score : null;
        $percentage = $attempt->percentage !== null ? (float) $attempt->percentage : null;
        $allowedDuration = (int) ($config['duration'] ?? $exam->duration ?? 0);
        $negativeEnabled = (bool) ($config['enable_negative_marking'] ?? $exam->enable_negative_marking);
        $negativeDeducted = $negativeEnabled
            ? abs((float) ($attempt->negative_marks_sum ?? 0))
            : null;

        $resultsVisible = $this->grading->resultsVisible($attempt);
        $submission = $this->submissionMeta($attempt);

        return [
            'id' => $attempt->id,
            'attempt_no' => (int) ($attempt->attempt_no ?: $attempt->id),
            'is_latest' => $isLatest,
            'status' => (string) $attempt->status,
            'status_label' => $this->statusLabel($attempt),
            'status_tone' => $this->statusTone($attempt),
            'result_label' => $attempt->passed === null ? null : ($attempt->passed ? 'Pass' : 'Fail'),
            'result_tone' => $attempt->passed === null ? null : ($attempt->passed ? 'pass' : 'fail'),
            'score' => $score,
            'percentage' => $percentage,
            'total_marks' => $totalMarks,
            'passing_marks' => $passingMarks,
            'negative_marks_deducted' => $negativeDeducted,
            'negative_marking_enabled' => $negativeEnabled,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'started_at_label' => $this->formatDateTime($startedAt, $timezone),
            'ended_at_label' => $this->formatDateTime($endedAt, $timezone),
            'submitted_at_label' => $this->formatDateTime($endedAt, $timezone),
            'timezone' => $timezone,
            'time_spent_seconds' => $timeSpentSeconds,
            'time_spent_label' => $timeSpentSeconds !== null ? $this->formatDuration($timeSpentSeconds) : '—',
            'allowed_duration_minutes' => $allowedDuration,
            'allowed_duration_label' => $allowedDuration > 0 ? $allowedDuration.' Minutes' : 'Untimed',
            'total_questions' => $totalQuestions,
            'attempted' => $attempted,
            'unattempted' => $unanswered,
            'correct' => $correct,
            'incorrect' => $incorrect,
            'skipped' => $unanswered,
            'marked_for_review' => $markedForReview,
            'attempted_label' => $totalQuestions > 0 ? $attempted.' / '.$totalQuestions : (string) $attempted,
            'progress_percent' => $totalQuestions > 0
                ? (int) round(($attempted / $totalQuestions) * 100)
                : 0,
            'submission_type' => $submission['type'],
            'submission_type_tone' => $submission['tone'],
            'submission_detail' => $submission['detail'],
            'paper_set' => $attempt->paper_set ? (int) $attempt->paper_set : null,
            'exam_mode' => ucfirst((string) ($exam->exam_mode ?: 'standard')),
            'device_type' => $this->deviceType($attempt),
            'browser' => $this->browser($attempt),
            'violations_count' => (int) ($attempt->violations_count ?? 0),
            'results_visible' => $resultsVisible,
            'result_url' => route('frontend.attempts.result', $attempt),
            'review_url' => $resultsVisible ? route('frontend.attempts.review', $attempt) : null,
            'download_enabled' => false,
        ];
    }

    protected function resolveTimeSpentSeconds(ExamAttempt $attempt): ?int
    {
        if ($attempt->started_at && $attempt->submitted_at) {
            return max(0, (int) $attempt->started_at->diffInSeconds($attempt->submitted_at));
        }

        if ($attempt->time_spent_seconds !== null) {
            return max(0, (int) $attempt->time_spent_seconds);
        }

        return null;
    }

    protected function resolveTimezone(ExamAttempt $attempt, Exam $exam): string
    {
        return (string) (
            $attempt->timezone
            ?: data_get($attempt->device_meta, 'timezone')
            ?: $attempt->device?->timezone
            ?: $exam->timezone
            ?: config('app.timezone')
        );
    }

    protected function statusLabel(ExamAttempt $attempt): string
    {
        $reason = (string) ($attempt->submission_reason ?? '');

        return match ($attempt->status) {
            'expired' => 'Time expired',
            'abandoned' => 'Abandoned',
            'graded' => 'Graded',
            'submitted' => match (true) {
                str_starts_with($reason, 'violation_') => 'Auto submitted',
                $reason === 'timer_expired' => 'Time expired',
                default => 'Submitted',
            },
            default => ucfirst(str_replace('_', ' ', (string) $attempt->status)),
        };
    }

    protected function statusTone(ExamAttempt $attempt): string
    {
        $reason = (string) ($attempt->submission_reason ?? '');

        if (str_starts_with($reason, 'violation_') || $attempt->status === 'abandoned') {
            return 'danger';
        }
        if ($attempt->status === 'expired' || $reason === 'timer_expired') {
            return 'warn';
        }
        if ($attempt->passed === true) {
            return 'success';
        }
        if ($attempt->passed === false) {
            return 'danger';
        }

        return 'info';
    }

    /**
     * @return array{type:string, tone:string, detail:string}
     */
    protected function submissionMeta(ExamAttempt $attempt): array
    {
        $reason = (string) ($attempt->submission_reason ?? '');
        $detail = $attempt->submission_reason
            ? $attempt->submissionReasonLabel()
            : '—';

        if (str_starts_with($reason, 'violation_')) {
            return [
                'type' => 'Rule Violation',
                'tone' => 'danger',
                'detail' => $detail,
            ];
        }

        if ($reason === 'timer_expired' || $attempt->status === 'expired') {
            return [
                'type' => 'Time Expired',
                'tone' => 'warn',
                'detail' => $detail !== '—' ? $detail : 'Time expired — exam auto-submitted',
            ];
        }

        if ($reason === 'manual' || $reason === '') {
            return [
                'type' => 'Submitted',
                'tone' => 'success',
                'detail' => $detail !== '—' ? $detail : 'Submitted by candidate',
            ];
        }

        return [
            'type' => 'Auto Submitted',
            'tone' => 'warn',
            'detail' => $detail,
        ];
    }

    protected function deviceType(ExamAttempt $attempt): string
    {
        $type = (string) (
            $attempt->device?->device_type
            ?: data_get($attempt->device_meta, 'device_type')
            ?: ''
        );

        return $type !== '' ? ucfirst($type) : '—';
    }

    protected function browser(ExamAttempt $attempt): string
    {
        $browser = (string) (
            $attempt->device?->browser
            ?: data_get($attempt->device_meta, 'browser')
            ?: ''
        );

        if ($browser === '') {
            return '—';
        }

        return $this->shortBrowserName($browser);
    }

    protected function shortBrowserName(string $raw): string
    {
        $value = trim($raw);
        if ($value === '') {
            return '—';
        }

        // Already a short label from device detection.
        if (strlen($value) <= 40 && ! str_contains($value, 'Mozilla/')) {
            return $value;
        }

        return match (true) {
            str_contains($value, 'Edg/') => 'Microsoft Edge',
            str_contains($value, 'OPR/') || str_contains($value, 'Opera') => 'Opera',
            str_contains($value, 'Firefox/') => 'Firefox',
            str_contains($value, 'Chrome/') && ! str_contains($value, 'Edg/') => 'Chrome',
            str_contains($value, 'Safari/') && ! str_contains($value, 'Chrome/') => 'Safari',
            default => \Illuminate\Support\Str::limit($value, 36, '…'),
        };
    }

    protected function formatDateTime(mixed $value, string $timezone): string
    {
        if (! $value) {
            return '—';
        }

        try {
            return $value->timezone($timezone)->format('d M Y, H:i');
        } catch (\Throwable) {
            return $value->format('d M Y, H:i');
        }
    }

    protected function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}
