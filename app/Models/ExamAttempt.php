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
        'policy_snapshot',
        'device_meta',
        'violations_summary',
        'session_token',
        'rules_agreed_at',
    ];

    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
            'answers' => 'array',
            'exam_config_snapshot' => 'array',
            'preferences_snapshot' => 'array',
            'policy_snapshot' => 'array',
            'device_meta' => 'array',
            'violations_summary' => 'array',
            'updated_by_history' => 'array',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'heartbeat_at' => 'datetime',
            'last_saved_at' => 'datetime',
            'rules_agreed_at' => 'datetime',
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

    public function submissionReasonLabel(): string
    {
        return self::labelForSubmissionReason($this->submission_reason);
    }

    /**
     * Normalized violation messages for candidate/admin display.
     * Prefers attempt.violations_summary JSON; falls back to relation rows.
     *
     * @return list<array{
     *     type:string,
     *     sequence:int,
     *     action_taken:string,
     *     title:string,
     *     message:string,
     *     advice:string,
     *     occurred_at:?string
     * }>
     */
    public function violationsList(): array
    {
        $summary = is_array($this->violations_summary) ? $this->violations_summary : [];
        if ($summary !== []) {
            return array_values(array_map(static function ($row): array {
                $row = is_array($row) ? $row : [];
                $type = (string) ($row['type'] ?? 'rule_warning');

                return [
                    'type' => $type,
                    'sequence' => (int) ($row['sequence'] ?? 0),
                    'action_taken' => (string) ($row['action_taken'] ?? 'warn'),
                    'title' => (string) ($row['title'] ?? \App\Support\ProctoringViolationMessages::title($type)),
                    'message' => (string) ($row['message'] ?? ''),
                    'advice' => (string) ($row['advice'] ?? ''),
                    'occurred_at' => isset($row['occurred_at']) ? (string) $row['occurred_at'] : null,
                ];
            }, $summary));
        }

        $this->loadMissing('violations');

        return $this->violations
            ->sortBy([
                ['occurred_at', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->map(static function (ExamAttemptViolation $violation): array {
                $type = (string) $violation->type;

                return [
                    'type' => $type,
                    'sequence' => (int) $violation->sequence,
                    'action_taken' => (string) ($violation->action_taken ?: 'warn'),
                    'title' => (string) ($violation->title ?: \App\Support\ProctoringViolationMessages::title($type)),
                    'message' => (string) ($violation->message ?: ''),
                    'advice' => (string) ($violation->advice ?: ''),
                    'occurred_at' => optional($violation->occurred_at)->toIso8601String(),
                ];
            })
            ->all();
    }

    public static function labelForSubmissionReason(?string $reason): string
    {
        $reason = trim((string) $reason);
        if ($reason === '') {
            return '—';
        }

        return match ($reason) {
            'manual' => 'Submitted by candidate',
            'timer_expired' => 'Time expired — exam auto-submitted',
            'violation_limit' => 'Maximum rule violations exceeded',
            'violation_multiple' => 'Multiple rule violations detected',
            'violation_tab_switch', 'violation_window_blur' => 'Maximum tab switches exceeded',
            'violation_fullscreen_exit' => 'Fullscreen exited multiple times',
            'violation_copy_attempt',
            'violation_paste_attempt',
            'violation_cut_attempt',
            'violation_drag_attempt',
            'violation_copy_paste' => 'Copy/paste violation limit exceeded',
            'violation_right_click' => 'Right-click violation limit exceeded',
            'violation_devtools_open' => 'Developer tools violation limit exceeded',
            'violation_page_refresh' => 'Page refresh violation limit exceeded',
            'violation_navigation_back' => 'Back navigation violation limit exceeded',
            'violation_camera_disabled' => 'Camera disabled during the exam',
            'violation_microphone_disabled' => 'Microphone disabled during the exam',
            'violation_media_lost' => 'Camera or microphone connection lost',
            'violation_session_warning' => 'Session warning limit exceeded',
            default => ucwords(str_replace('_', ' ', $reason)),
        };
    }
}
