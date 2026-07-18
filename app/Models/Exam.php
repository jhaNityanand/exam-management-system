<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'tags',
        'pricing_option',
        'exam_currency',
        'exam_amount',
        'selected_discounts',
        'custom_discounts',

        // Timer & Duration
        'duration',
        'enable_exam_timer',
        'auto_submit_on_timer_end',

        // Scheduling
        'schedule_type',
        'scheduled_start',
        'scheduled_end',

        // Attempts
        'attempt_limit_type',
        'max_attempts',

        // Scoring
        'pass_percentage',
        'total_marks',
        'passing_marks',
        'negative_mark_per_question',
        'enable_negative_marking',
        'negative_marking_type',
        'fix_marks_each_question',

        // Question Configuration
        'total_questions',
        'use_question_pool',
        'maximum_questions',
        'fixed_questions',
        'fixed_paper_set',
        'paper_sets',
        'fix_category_questions',
        'fix_category_marks',
        'distribution_type',
        'selected_categories',
        'extra_questions_categories',
        'extra_questions_allocations',
        'extra_marks_allocations',
        'question_marks_filter',
        'category_question_rules',

        // Shuffle
        'shuffle_questions',
        'shuffle_categories',
        'shuffle_options',

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
            // Booleans
            'enable_exam_timer'          => 'boolean',
            'auto_submit_on_timer_end'   => 'boolean',
            'use_question_pool'          => 'boolean',
            'fixed_questions'            => 'boolean',
            'fixed_paper_set'            => 'boolean',
            'shuffle_questions'          => 'boolean',
            'shuffle_categories'         => 'boolean',
            'shuffle_options'            => 'boolean',
            'enable_negative_marking'    => 'boolean',
            'fix_marks_each_question'    => 'boolean',
            'fix_category_questions'     => 'boolean',
            'fix_category_marks'         => 'boolean',
            'ai_generated'               => 'boolean',
            'ai_improve'                 => 'boolean',

            // Dates
            'scheduled_start'            => 'datetime',
            'scheduled_end'              => 'datetime',

            // Numbers
            'exam_amount'                => 'decimal:2',

            // JSON
            'tags'                       => 'array',
            'exam_format'                => 'array',
            'selected_categories'        => 'array',
            'extra_questions_categories' => 'array',
            'extra_questions_allocations'=> 'array',
            'extra_marks_allocations'    => 'array',
            'question_marks_filter'      => 'array',
            'category_question_rules'    => 'array',
            'imported_candidates'             => 'array',
            'manual_candidate_emails'         => 'array',
            'free_imported_candidates'        => 'array',
            'free_manual_candidate_emails'    => 'array',
            'selected_discounts'              => 'array',
            'custom_discounts'                => 'array',
            'predefined_instruction_rules'    => 'array',
            'updated_by_history'              => 'array',
        ];
    }

    public function selectedQuestionCategories()
    {
        return $this->belongsToMany(QuestionCategory::class, 'exam_question_category', 'exam_id', 'question_category_id');
    }

    // ── Relationships ─────────────────────────────────────────────────────────

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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'exam_question')
            ->withPivot(['sort_order', 'marks_override', 'status'])
            ->withTimestamps();
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}
