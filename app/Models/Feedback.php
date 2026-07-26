<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Feedback extends Model
{
    use BelongsToOrganization, HasAuditTrails, HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SPAM = 'spam';

    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'feedback';

    protected $fillable = [
        'organization_id',
        'user_id',
        'exam_id',
        'exam_attempt_id',
        'feedbackable_type',
        'feedbackable_id',
        'rating',
        'title',
        'message',
        'status',
        'is_public',
        'source',
        'locale',
        'meta',
        'ip_address',
        'user_agent',
        'created_by',
        'updated_by',
        'deleted_by',
        'updated_by_history',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_public' => 'boolean',
            'meta' => 'array',
            'updated_by_history' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Feedback $feedback) {
            if (Auth::check() && empty($feedback->deleted_by)) {
                $feedback->deleted_by = Auth::id();
                $feedback->saveQuietly();
            }
        });
    }

    public function feedbackable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function scopePublicVisible(Builder $query): Builder
    {
        return $query
            ->where('is_public', true)
            ->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForFeedbackable(Builder $query, Model $model): Builder
    {
        return $query
            ->where('feedbackable_type', $model->getMorphClass())
            ->where('feedbackable_id', $model->getKey());
    }

    public function isVisiblePublicly(): bool
    {
        return $this->is_public && $this->status === self::STATUS_ACTIVE;
    }
}
