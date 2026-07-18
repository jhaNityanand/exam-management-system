<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditTrails;

class ExamAttempt extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrails;

    protected $fillable = [
        'exam_id',
        'user_id',
        'status',
        'created_by',
        'updated_by',
        'updated_by_history',
        'score',
        'passed',
        'started_at',
        'submitted_at',
        'answers', // JSON snapshot of answers submitted
    ];

    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
            'answers' => 'array',
            'updated_by_history' => 'array',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attemptQuestions()
    {
        return $this->hasMany(ExamAttemptQuestion::class)->orderBy('position');
    }
}
