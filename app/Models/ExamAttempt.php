<?php

namespace App\Models;

use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamAttempt extends Model
{
    use HasAuditTrails, HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_ABANDONED = 'abandoned';

    public const STATUS_GRADED = 'graded';

    protected $fillable = [
        'exam_id',
        'organization_id',
        'user_id',
        'attempt_no',
        'status',
        'created_by',
        'updated_by',
        'updated_by_history',
        'score',
        'percentage',
        'passed',
        'correct_count',
        'wrong_count',
        'unanswered_count',
        'time_spent_seconds',
        'started_at',
        'expires_at',
        'heartbeat_at',
        'last_saved_at',
        'revision',
        'paper_set',
        'timezone',
        'submitted_at',
        'submission_reason',
        'result_released_at',
        'answers',
        'exam_config_snapshot',
        'preferences_snapshot',
        'device_meta',
    ];

    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
            'answers' => 'array',
            'exam_config_snapshot' => 'array',
            'preferences_snapshot' => 'array',
            'device_meta' => 'array',
            'updated_by_history' => 'array',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'heartbeat_at' => 'datetime',
            'last_saved_at' => 'datetime',
            'submitted_at' => 'datetime',
            'result_released_at' => 'datetime',
            'score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'revision' => 'integer',
            'attempt_no' => 'integer',
            'paper_set' => 'integer',
            'correct_count' => 'integer',
            'wrong_count' => 'integer',
            'unanswered_count' => 'integer',
            'time_spent_seconds' => 'integer',
        ];
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function attemptQuestions()
    {
        return $this->hasMany(ExamAttemptQuestion::class)->orderBy('position');
    }

    public function attemptAnswers()
    {
        return $this->hasMany(ExamAttemptAnswer::class);
    }

    public function violations()
    {
        return $this->hasMany(ExamAttemptViolation::class);
    }

    public function events()
    {
        return $this->hasMany(ExamAttemptEvent::class);
    }

    public function device()
    {
        return $this->hasOne(ExamAttemptDevice::class);
    }

    public function snapshots()
    {
        return $this->hasMany(ExamAttemptSnapshot::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_IN_PROGRESS], true);
    }
}
