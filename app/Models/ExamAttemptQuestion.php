<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAttemptQuestion extends Model
{
    protected $fillable = [
        'exam_attempt_id',
        'question_id',
        'position',
        'category_id',
        'marks',
        'question_snapshot',
        'option_order',
        'selection_meta',
    ];

    protected function casts(): array
    {
        return [
            'question_snapshot' => 'array',
            'option_order' => 'array',
            'selection_meta' => 'array',
            'marks' => 'integer',
            'position' => 'integer',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class)->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(QuestionCategory::class, 'category_id')->withTrashed();
    }

    /**
     * Candidate-safe payload without correct answers.
     *
     * @return array<string, mixed>
     */
    public function toCandidatePayload(): array
    {
        $snapshot = is_array($this->question_snapshot) ? $this->question_snapshot : [];
        $question = $this->sanitizeCandidateQuestion($snapshot);
        $meta = $this->selection_meta ?? [];

        return [
            'id' => $this->id,
            'position' => $this->position,
            'question_id' => $this->question_id,
            'category_id' => $this->category_id,
            'marks' => $this->marks,
            'option_order' => $this->option_order,
            'part_id' => isset($meta['part_id']) ? (int) $meta['part_id'] : null,
            'part_name' => isset($meta['part_name']) ? (string) $meta['part_name'] : null,
            'part_sort_order' => isset($meta['part_sort_order']) ? (int) $meta['part_sort_order'] : null,
            'question' => $question,
        ];
    }

    /**
     * Strip answer keys and other sensitive fields from the candidate-facing snapshot.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    protected function sanitizeCandidateQuestion(array $snapshot): array
    {
        $blocked = [
            'correct_answer',
            'correct_answers',
            'explanation',
            'solution',
            'answer',
            'answers',
            'answer_key',
            'answer_keys',
            'grading_rubric',
            'rubric',
        ];

        foreach ($blocked as $key) {
            unset($snapshot[$key]);
        }

        return $snapshot;
    }
}
