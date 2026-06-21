<?php

namespace App\Models;

use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use HasAuditTrails, HasFactory, SoftDeletes;

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
        'total_categories',
        'paper_sets',
        'fix_category_questions',
        'distribution_type',
        'selected_categories',
        'extra_questions_categories',
        'extra_questions_allocations',
        'question_marks_filter',
        'category_question_rules',

        // Shuffle
        'shuffle_questions',
        'shuffle_options',

        // Candidate Access
        'imported_candidates',
        'manual_candidate_emails',

        // SEO / Metadata
        'meta_title',
        'meta_description',
        'meta_keywords',
        'slug',
        'canonical_url',
        'og_title',
        'og_description',
    ];

    protected function casts(): array
    {
        return [
            // Booleans
            'enable_exam_timer'          => 'boolean',
            'auto_submit_on_timer_end'   => 'boolean',
            'shuffle_questions'          => 'boolean',
            'shuffle_options'            => 'boolean',
            'enable_negative_marking'    => 'boolean',
            'fix_marks_each_question'    => 'boolean',
            'fix_category_questions'     => 'boolean',

            // Dates
            'scheduled_start'            => 'datetime',
            'scheduled_end'              => 'datetime',

            // JSON
            'tags'                       => 'array',
            'selected_categories'        => 'array',
            'extra_questions_categories' => 'array',
            'extra_questions_allocations'=> 'array',
            'question_marks_filter'      => 'array',
            'category_question_rules'    => 'array',
            'imported_candidates'        => 'array',
            'manual_candidate_emails'    => 'array',
            'updated_by_history'         => 'array',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function category()
    {
        return $this->belongsTo(QuestionCategory::class);
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

    public function scopeForOrg($query, int $orgId)
    {
        return $query->where('organization_id', $orgId);
    }
}
