<?php

namespace App\Services\CandidateExam;

use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptEvent;
use Illuminate\Support\Facades\DB;

class ExamGradingService
{
    public function submit(ExamAttempt $attempt, string $reason = 'manual', bool $auto = false): ExamAttempt
    {
        if (in_array($attempt->status, ['submitted', 'graded', 'expired'], true)) {
            return $attempt->fresh(['attemptAnswers', 'attemptQuestions', 'exam']);
        }

        return DB::transaction(function () use ($attempt, $reason, $auto) {
            $attempt = ExamAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            if (in_array($attempt->status, ['submitted', 'graded', 'expired'], true)) {
                return $attempt;
            }

            $attempt->loadMissing(['attemptQuestions', 'attemptAnswers', 'exam']);
            $config = $attempt->exam_config_snapshot ?: [];
            $negativeEnabled = (bool) ($config['enable_negative_marking'] ?? $attempt->exam?->enable_negative_marking);
            $negativePer = (float) ($config['negative_mark_per_question'] ?? $attempt->exam?->negative_mark_per_question ?? 0);

            $score = 0.0;
            $correct = 0;
            $wrong = 0;
            $unanswered = 0;

            foreach ($attempt->attemptQuestions as $question) {
                $answer = $attempt->attemptAnswers->firstWhere('exam_attempt_question_id', $question->id);
                $snapshot = $question->question_snapshot ?? [];
                $marks = (float) ($question->marks ?: ($snapshot['marks'] ?? 0));
                $result = $this->gradeQuestion($snapshot, $answer?->answer_value);

                $awarded = 0.0;
                $isCorrect = null;
                $gradingStatus = 'skipped';

                if ($result['gradable'] === false) {
                    $gradingStatus = 'manual';
                    if (! $answer || ! $answer->is_answered) {
                        $unanswered++;
                    }
                } elseif (! $answer || ! $answer->is_answered) {
                    $unanswered++;
                    $gradingStatus = 'auto';
                    $isCorrect = false;
                } elseif ($result['correct']) {
                    $awarded = $marks;
                    $correct++;
                    $isCorrect = true;
                    $gradingStatus = 'auto';
                } else {
                    $wrong++;
                    $isCorrect = false;
                    $gradingStatus = 'auto';
                    if ($negativeEnabled && $negativePer > 0) {
                        $awarded = -1 * ($marks * $negativePer);
                    }
                }

                $score += $awarded;

                ExamAttemptAnswer::query()->updateOrCreate(
                    [
                        'exam_attempt_id' => $attempt->id,
                        'exam_attempt_question_id' => $question->id,
                    ],
                    [
                        'answer_value' => $answer?->answer_value,
                        'is_marked_for_review' => (bool) ($answer?->is_marked_for_review),
                        'is_visited' => (bool) ($answer?->is_visited ?? false),
                        'is_answered' => (bool) ($answer?->is_answered ?? false),
                        'answered_at' => $answer?->answered_at,
                        'awarded_marks' => $awarded,
                        'is_correct' => $isCorrect,
                        'grading_status' => $gradingStatus,
                    ]
                );
            }

            $totalMarks = (float) ($config['total_marks'] ?? $attempt->exam?->total_marks ?? 0);
            $passingMarks = (float) ($config['passing_marks'] ?? $attempt->exam?->passing_marks ?? 0);
            $percentage = $totalMarks > 0 ? round(($score / $totalMarks) * 100, 2) : 0.0;
            $started = $attempt->started_at ?? now();
            $timeSpent = max(0, $started->diffInSeconds(now()));

            $releaseMode = $config['result_release_mode'] ?? $attempt->exam?->result_release_mode ?? 'immediate';
            $releaseAt = null;
            if ($releaseMode === 'immediate') {
                $releaseAt = now();
            } elseif ($releaseMode === 'scheduled') {
                $releaseAt = $attempt->exam?->result_release_at;
            }

            $status = $reason === 'timer_expired' ? 'expired' : 'submitted';

            $attempt->fill([
                'status' => $status,
                'score' => round($score, 2),
                'percentage' => $percentage,
                'passed' => $score >= $passingMarks,
                'correct_count' => $correct,
                'wrong_count' => $wrong,
                'unanswered_count' => $unanswered,
                'time_spent_seconds' => $timeSpent,
                'submitted_at' => now(),
                'submission_reason' => $auto ? $reason : 'manual',
                'result_released_at' => $releaseAt,
                'answers' => $attempt->attemptAnswers()->get(['exam_attempt_question_id', 'answer_value', 'is_marked_for_review'])->toArray(),
            ])->save();

            ExamAttemptEvent::query()->create([
                'exam_attempt_id' => $attempt->id,
                'event' => 'submitted',
                'payload' => ['reason' => $reason, 'auto' => $auto, 'score' => $attempt->score],
                'occurred_at' => now(),
            ]);

            return $attempt->fresh(['attemptAnswers', 'attemptQuestions', 'exam']);
        });
    }

    public function resultsVisible(ExamAttempt $attempt): bool
    {
        if (! in_array($attempt->status, ['submitted', 'graded', 'expired'], true)) {
            return false;
        }

        $mode = $attempt->exam_config_snapshot['result_release_mode']
            ?? $attempt->exam?->result_release_mode
            ?? 'immediate';

        if ($mode === 'never') {
            return false;
        }
        if ($mode === 'manual') {
            return $attempt->result_released_at !== null;
        }
        if ($mode === 'scheduled') {
            $at = $attempt->result_released_at ?? $attempt->exam?->result_release_at;

            return $at !== null && now()->greaterThanOrEqualTo($at);
        }

        return true;
    }

    /**
     * @return array{gradable:bool, correct:bool}
     */
    protected function gradeQuestion(array $snapshot, mixed $answerValue): array
    {
        $type = $snapshot['type'] ?? 'mcq';
        $allowsMultiple = (bool) ($snapshot['allows_multiple'] ?? false);

        if (in_array($type, ['short_answer', 'long_answer'], true)) {
            return ['gradable' => false, 'correct' => false];
        }

        if ($type === 'fill_blank') {
            $expected = $snapshot['correct_answer'] ?? ($snapshot['correct_answers'][0] ?? null);
            $given = is_array($answerValue) ? ($answerValue['text'] ?? $answerValue[0] ?? '') : (string) $answerValue;

            return [
                'gradable' => true,
                'correct' => strcasecmp(trim((string) $given), trim((string) $expected)) === 0,
            ];
        }

        if ($type === 'true_false') {
            $expected = (string) ($snapshot['correct_answer'] ?? '');
            $given = is_array($answerValue) ? (string) ($answerValue['value'] ?? $answerValue[0] ?? '') : (string) $answerValue;

            return ['gradable' => true, 'correct' => strcasecmp(trim($given), trim($expected)) === 0];
        }

        // MCQ / multi-select
        $expected = $snapshot['correct_answers'] ?? $snapshot['correct_answer'] ?? [];
        if (! is_array($expected)) {
            $expected = [$expected];
        }
        $expected = array_values(array_map('strval', $expected));
        sort($expected);

        $given = $answerValue;
        if (! is_array($given)) {
            $given = $given === null || $given === '' ? [] : [$given];
        }
        $given = array_values(array_map('strval', $given));
        sort($given);

        if ($allowsMultiple) {
            return ['gradable' => true, 'correct' => $expected === $given];
        }

        return [
            'gradable' => true,
            'correct' => ($given[0] ?? null) !== null && in_array($given[0], $expected, true),
        ];
    }
}
