<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ExamAttemptSnapshot extends Model
{
    protected $fillable = [
        'exam_attempt_id',
        'type',
        'path',
        'disk',
        'verification_status',
        'challenge_token',
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

    public function url(): ?string
    {
        if (! $this->path) {
            return null;
        }

        $disk = $this->disk ?: 'local';
        if ($disk === 'public') {
            return Storage::disk($disk)->url($this->path);
        }

        return null;
    }
}
