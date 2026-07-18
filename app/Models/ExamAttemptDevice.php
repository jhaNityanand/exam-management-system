<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAttemptDevice extends Model
{
    protected $fillable = [
        'exam_attempt_id',
        'ip_address',
        'user_agent',
        'browser',
        'device_type',
        'os',
        'screen_resolution',
        'timezone',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }
}
