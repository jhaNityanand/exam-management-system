<?php

namespace App\Services\CandidateExam;

use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamAnswerService
{
    public function __construct(
        protected ExamSessionService $sessions
    ) {}

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{revision:int, saved:int, answers:list<array<string,mixed>>}
     */
    public function saveBatch(ExamAttempt $attempt, array $items, ?int $clientRevision = null): array
    {
        $attempt = $this->sessions->expireIfNeeded($attempt);

        if (! in_array($attempt->status, ['active', 'in_progress'], true)) {
            throw ValidationException::withMessages([
                'attempt' => 'This attempt is locked and can no longer accept answers.',
            ]);
        }

        if ($attempt->expires_at && now()->greaterThan($attempt->expires_at)) {
            throw ValidationException::withMessages([
                'attempt' => 'Exam time has expired.',
            ]);
        }

        return DB::transaction(function () use ($attempt, $items) {
            $saved = [];
            $questionIds = collect($items)->pluck('exam_attempt_question_id')->filter()->map(fn ($id) => (int) $id)->all();
            $validQuestionIds = ExamAttemptQuestion::query()
                ->where('exam_attempt_id', $attempt->id)
                ->whereIn('id', $questionIds)
                ->pluck('id')
                ->all();

            foreach ($items as $item) {
                $qid = (int) ($item['exam_attempt_question_id'] ?? 0);
                if (! in_array($qid, $validQuestionIds, true)) {
                    continue;
                }

                $value = $item['answer_value'] ?? null;
                $isAnswered = $this->isAnswered($value);

                $answer = ExamAttemptAnswer::query()->firstOrNew([
                    'exam_attempt_id' => $attempt->id,
                    'exam_attempt_question_id' => $qid,
                ]);

                $answer->fill([
                    'answer_value' => $value,
                    'is_marked_for_review' => (bool) ($item['is_marked_for_review'] ?? false),
                    'is_visited' => (bool) ($item['is_visited'] ?? true),
                    'is_answered' => $isAnswered,
                    'answered_at' => $isAnswered ? now() : null,
                    'revision' => ((int) $answer->revision) + 1,
                ]);
                $answer->save();
                $saved[] = [
                    'exam_attempt_question_id' => $answer->exam_attempt_question_id,
                    'answer_value' => $answer->answer_value,
                    'is_marked_for_review' => $answer->is_marked_for_review,
                    'is_visited' => $answer->is_visited,
                    'is_answered' => $answer->is_answered,
                    'revision' => $answer->revision,
                ];
            }

            $attempt->revision = ((int) $attempt->revision) + 1;
            $attempt->last_saved_at = now();
            $attempt->heartbeat_at = now();
            $attempt->save();

            return [
                'revision' => $attempt->revision,
                'saved' => count($saved),
                'answers' => $saved,
            ];
        });
    }

    protected function isAnswered(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        if (is_string($value)) {
            return trim($value) !== '';
        }
        if (is_array($value)) {
            return collect($value)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
        }

        return true;
    }
}
