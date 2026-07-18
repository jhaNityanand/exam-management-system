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
        return $this->belongsTo(Question::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(QuestionCategory::class, 'category_id');
    }

    /**
     * Candidate-safe payload without correct answers.
     *
     * @return array<string, mixed>
     */
    public function toCandidatePayload(): array
    {
        $snapshot = $this->question_snapshot ?? [];
        unset($snapshot['correct_answer'], $snapshot['correct_answers'], $snapshot['explanation']);

        return [
            'id' => $this->id,
            'position' => $this->position,
            'question_id' => $this->question_id,
            'category_id' => $this->category_id,
            'marks' => $this->marks,
            'option_order' => $this->option_order,
            'question' => $snapshot,
        ];
    }
}
