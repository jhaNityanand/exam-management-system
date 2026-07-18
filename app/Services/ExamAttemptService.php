<?php

namespace App\Services;

use App\Exceptions\AttemptQuestionShortageException;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamAttemptService
{
    public function __construct(protected AttemptQuestionSelector $selector) {}

    /**
     * Start or resume an attempt. Question assignment is resolved once and reused.
     *
     * @throws ValidationException
     * @throws AttemptQuestionShortageException
     */
    public function start(Exam $exam, User $user): ExamAttempt
    {
        $this->assertExamAvailable($exam);
        $this->assertAttemptAllowed($exam, $user);

        // Fast resume path — avoid holding row locks while loading questions.
        $existing = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'in_progress'])
            ->latest('id')
            ->first();

        if ($existing && $existing->attemptQuestions()->exists()) {
            return $existing->load(['attemptQuestions' => fn ($q) => $q->orderBy('position')]);
        }

        return DB::transaction(function () use ($exam, $user) {
            $active = ExamAttempt::query()
                ->where('exam_id', $exam->id)
                ->where('user_id', $user->id)
                ->whereIn('status', ['active', 'in_progress'])
                ->lockForUpdate()
                ->first();

            if ($active) {
                if ($active->attemptQuestions()->exists()) {
                    return $active->load(['attemptQuestions' => fn ($q) => $q->orderBy('position')]);
                }

                $this->assignQuestions($active, $exam);

                return $active->load(['attemptQuestions' => fn ($q) => $q->orderBy('position')]);
            }

            $attemptNo = ExamAttempt::query()
                ->where('exam_id', $exam->id)
                ->where('user_id', $user->id)
                ->count() + 1;

            $attempt = ExamAttempt::create([
                'exam_id' => $exam->id,
                'organization_id' => $exam->organization_id,
                'user_id' => $user->id,
                'attempt_no' => $attemptNo,
                'status' => 'active',
                'started_at' => now(),
                'created_by' => $user->id,
            ]);

            $this->assignQuestions($attempt, $exam);

            return $attempt->load(['attemptQuestions' => fn ($q) => $q->orderBy('position')]);
        }, 3);
    }

    protected function assignQuestions(ExamAttempt $attempt, Exam $exam): void
    {
        $mode = $this->selector->resolveMode($exam);
        $questions = $this->selector->select($exam);

        if ($exam->shuffle_questions) {
            shuffle($questions);
        }

        $rows = [];
        $now = now();
        foreach (array_values($questions) as $index => $question) {
            /** @var Question $question */
            $options = is_array($question->options) ? $question->options : [];
            $optionKeys = array_keys($options);
            if ($exam->shuffle_options) {
                shuffle($optionKeys);
            }

            $rows[] = [
                'exam_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'position' => $index + 1,
                'category_id' => $question->category_id,
                'marks' => (int) ((isset($question->pivot) ? $question->pivot->marks_override : null) ?? $question->marks ?? 0),
                'question_snapshot' => json_encode([
                    'id' => $question->id,
                    'body' => $question->body,
                    'type' => $question->type,
                    'allows_multiple' => (bool) $question->allows_multiple,
                    'options' => $options,
                    'correct_answer' => $question->correct_answer,
                    'correct_answers' => $question->correct_answers,
                    'explanation' => $question->explanation,
                    'difficulty' => $question->difficulty,
                    'marks' => $question->marks,
                ], JSON_THROW_ON_ERROR),
                'option_order' => json_encode(array_values($optionKeys), JSON_THROW_ON_ERROR),
                'selection_meta' => json_encode([
                    'mode' => $mode,
                    'source_question_id' => $question->id,
                ], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        ExamAttemptQuestion::query()->where('exam_attempt_id', $attempt->id)->delete();
        foreach (array_chunk($rows, 100) as $chunk) {
            ExamAttemptQuestion::query()->insert($chunk);
        }
    }

    protected function assertExamAvailable(Exam $exam): void
    {
        if ($exam->status !== 'published') {
            throw ValidationException::withMessages([
                'exam' => 'This exam is not available.',
            ]);
        }

        $now = now();
        if ($exam->schedule_type === 'fixed_window') {
            if ($exam->scheduled_start && $now->lt($exam->scheduled_start)) {
                throw ValidationException::withMessages([
                    'exam' => 'This exam has not started yet.',
                ]);
            }
            if ($exam->scheduled_end && $now->gt($exam->scheduled_end)) {
                throw ValidationException::withMessages([
                    'exam' => 'This exam has ended.',
                ]);
            }
        }
    }

    protected function assertAttemptAllowed(Exam $exam, User $user): void
    {
        $limitType = $exam->attempt_limit_type ?: 'once';
        $maxAttempts = (int) ($exam->max_attempts ?? 1);

        if ($limitType === 'unlimited' || $maxAttempts === 0) {
            return;
        }

        $activeExists = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'in_progress'])
            ->exists();
        if ($activeExists) {
            return;
        }

        $completedCount = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'abandoned', 'expired', 'graded'])
            ->count();

        $allowed = $limitType === 'once' ? 1 : max(1, $maxAttempts);
        if ($completedCount >= $allowed) {
            throw ValidationException::withMessages([
                'exam' => 'You have reached the maximum number of attempts for this exam.',
            ]);
        }
    }

    /**
     * Candidate-facing payload without correct answers.
     *
     * @return array<string, mixed>
     */
    public function toCandidateStartPayload(ExamAttempt $attempt): array
    {
        $attempt->loadMissing(['attemptQuestions', 'exam']);

        return [
            'attempt' => [
                'id' => $attempt->id,
                'exam_id' => $attempt->exam_id,
                'status' => $attempt->status,
                'started_at' => optional($attempt->started_at)?->toIso8601String(),
            ],
            'exam' => [
                'id' => $attempt->exam?->id,
                'title' => $attempt->exam?->title,
                'duration' => $attempt->exam?->duration,
                'total_questions' => $attempt->exam?->total_questions,
                'total_marks' => $attempt->exam?->total_marks,
            ],
            'questions' => $attempt->attemptQuestions
                ->sortBy('position')
                ->values()
                ->map(fn (ExamAttemptQuestion $row) => $row->toCandidatePayload())
                ->all(),
        ];
    }
}
