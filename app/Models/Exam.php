<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use BelongsToOrganization, HasAuditTrails, HasFactory, SoftDeletes;

    protected $fillable = [
        // Identity
        'organization_id',
        'category_id',
        'created_by',
        'updated_by',
        'updated_by_history',
        'title',
        'description',
        'status',
        'exam_mode',
        'exam_format',
        'difficulty_level',
        'visibility',
        'demo_enabled',
        'language',
        'timezone',
        'tags',
        'pricing_option',
        'exam_currency',
        'exam_amount',
        'selected_discounts',
        'custom_discounts',

        // Timer & Duration (duration = sum of parts)
        'duration',
        'enable_exam_timer',
        'auto_submit_on_timer_end',

        // Scheduling
        'schedule_type',
        'scheduled_start',
        'scheduled_end',
        'registration_deadline',

        // Attempts
        'attempt_limit_type',
        'max_attempts',

        // Scoring (totals = sum of parts; passing/negative stay exam-level)
        'pass_percentage',
        'total_marks',
        'passing_marks',
        'total_questions',
        'result_release_mode',
        'result_release_at',
        'negative_mark_per_question',
        'enable_negative_marking',
        'negative_marking_type',

        // Candidate Access
        'imported_candidates',
        'manual_candidate_emails',
        'free_imported_candidates',
        'free_manual_candidate_emails',

        // SEO / Metadata
        'meta_title',
        'meta_description',
        'meta_keywords',
        'slug',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image_id',
        'banner_image_id',
        'robots',
        'schema_markup',
        'instructions',
        'predefined_instruction_rules',

        // AI flags
        'ai_generated',
        'ai_improve',
    ];

    protected function casts(): array
    {
        return [
            'enable_exam_timer' => 'boolean',
            'auto_submit_on_timer_end' => 'boolean',
            'enable_negative_marking' => 'boolean',
            'ai_generated' => 'boolean',
            'ai_improve' => 'boolean',
            'demo_enabled' => 'boolean',

            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
            'registration_deadline' => 'datetime',
            'result_release_at' => 'datetime',

            'exam_amount' => 'decimal:2',

            'tags' => 'array',
            'exam_format' => 'array',
            'imported_candidates' => 'array',
            'manual_candidate_emails' => 'array',
            'free_imported_candidates' => 'array',
            'free_manual_candidate_emails' => 'array',
            'selected_discounts' => 'array',
            'custom_discounts' => 'array',
            'predefined_instruction_rules' => 'array',
            'updated_by_history' => 'array',
        ];
    }

    public function parts(): HasMany
    {
        return $this->hasMany(ExamPart::class)->orderBy('sort_order');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function category()
    {
        return $this->belongsTo(ExamCategory::class, 'category_id');
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Gallery::class, 'og_image_id');
    }

    public function bannerImage(): BelongsTo
    {
        return $this->belongsTo(Gallery::class, 'banner_image_id');
    }

    public function proctoringPolicy()
    {
        return $this->hasOne(ExamProctoringPolicy::class);
    }

    public function entitlements()
    {
        return $this->hasMany(ExamEntitlement::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function feedback()
    {
        return $this->morphMany(Feedback::class, 'feedbackable');
    }

    /**
     * Flat question IDs attached via any part pivot (pool/fixed modes).
     *
     * @return list<int>
     */
    public function attachedQuestionIds(): array
    {
        $this->loadMissing('parts.questions:id');

        return $this->parts
            ->flatMap(fn (ExamPart $part) => $part->questions->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function attachedQuestionsCount(): int
    {
        return count($this->attachedQuestionIds());
    }

    public function bannerUrl(): ?string
    {
        return $this->bannerImage?->file_url ?? $this->ogImage?->file_url;
    }

    public function isPaid(): bool
    {
        return ($this->pricing_option ?: 'free') === 'paid' || (float) ($this->exam_amount ?? 0) > 0;
    }

    public function scopePublicCatalog($query)
    {
        return $query->published()->where('visibility', 'public');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}
