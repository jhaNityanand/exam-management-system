<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use BelongsToOrganization, HasAuditTrails, HasFactory, SoftDeletes;

    protected $fillable = [
        // Relations & Audit
        'organization_id',
        'category_id',
        'import_question_id',
        'created_by',
        'updated_by',
        'updated_by_history',

        // Content
        'body',
        'title',
        'type',
        'allows_multiple',
        'options',
        'correct_answer',
        'correct_answers',
        'explanation',
        'reference',

        // Scoring & Classification
        'marks_type',
        'marks_list',
        'marks',
        'difficulty',
        'status',
        'is_public',
        'show_explanation_publicly',
        'view_count',
        'public_tags',

        // SEO / Metadata
        'meta_title',
        'meta_description',
        'meta_keywords',
        'slug',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image_id',
        'robots',
        'schema_markup',

        // AI flags
        'ai_generated',
        'ai_improve',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_answers' => 'array',
            'allows_multiple' => 'boolean',
            'updated_by_history' => 'array',
            'marks_list' => 'array',
            'ai_generated' => 'boolean',
            'ai_improve' => 'boolean',
            'is_public' => 'boolean',
            'show_explanation_publicly' => 'boolean',
            'view_count' => 'integer',
            'public_tags' => 'array',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function category()
    {
        return $this->belongsTo(QuestionCategory::class, 'category_id');
    }

    public function importQuestion(): BelongsTo
    {
        return $this->belongsTo(ImportQuestion::class, 'import_question_id');
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Gallery::class, 'og_image_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'exam_question')
            ->withPivot(['sort_order', 'marks_override', 'status'])
            ->withTimestamps();
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopePubliclyVisible($query)
    {
        return $query->where('status', 'active')->where('is_public', true)->whereNotNull('slug');
    }

    public function publicTitle(): string
    {
        if (filled($this->title)) {
            return (string) $this->title;
        }

        $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string) $this->body)));

        return $plain !== '' ? \Illuminate\Support\Str::limit($plain, 120, '') : 'Question #'.$this->id;
    }

    /**
     * Candidate/public-safe payload without correct answers.
     *
     * @return array<string, mixed>
     */
    public function toPublicPayload(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->publicTitle(),
            'slug' => $this->slug,
            'body' => $this->body,
            'type' => $this->type,
            'difficulty' => $this->difficulty,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
            'tags' => $this->public_tags ?? [],
            'explanation' => $this->show_explanation_publicly ? $this->explanation : null,
            'reference' => $this->reference,
            'options' => collect($this->options ?? [])->map(function ($option, $index) {
                if (is_array($option)) {
                    return [
                        'key' => (string) ($option['key'] ?? $index),
                        'text' => $option['text'] ?? $option['label'] ?? '',
                    ];
                }

                return ['key' => (string) $index, 'text' => (string) $option];
            })->values()->all(),
        ];
    }
}
