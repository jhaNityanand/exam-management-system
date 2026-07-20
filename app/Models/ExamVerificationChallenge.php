<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamVerificationChallenge extends Model
{
    protected $fillable = [
        'organization_id',
        'exam_id',
        'user_id',
        'token',
        'required_checks',
        'completed_checks',
        'selfie_path',
        'selfie_disk',
        'expires_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'required_checks' => 'array',
            'completed_checks' => 'array',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return $this->consumed_at === null
            && $this->expires_at
            && $this->expires_at->isFuture();
    }
}
