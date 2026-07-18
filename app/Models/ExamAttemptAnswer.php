<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAttemptAnswer extends Model
{
    protected $fillable = [
        'exam_attempt_id',
        'exam_attempt_question_id',
        'answer_value',
        'is_marked_for_review',
        'is_visited',
        'is_answered',
        'answered_at',
        'revision',
        'awarded_marks',
        'is_correct',
        'grading_status',
    ];

    protected function casts(): array
    {
        return [
            'answer_value' => 'json',
            'is_marked_for_review' => 'boolean',
            'is_visited' => 'boolean',
            'is_answered' => 'boolean',
            'answered_at' => 'datetime',
            'revision' => 'integer',
            'awarded_marks' => 'decimal:2',
            'is_correct' => 'boolean',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function attemptQuestion(): BelongsTo
    {
        return $this->belongsTo(ExamAttemptQuestion::class, 'exam_attempt_question_id');
    }
}
