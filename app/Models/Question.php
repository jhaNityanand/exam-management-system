<?php

namespace App\Models;

use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasAuditTrails, HasFactory, SoftDeletes;

    protected $fillable = [
        // Relations & Audit
        'organization_id',
        'category_id',
        'created_by',
        'updated_by',
        'updated_by_history',

        // Content
        'body',
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

        // SEO / Metadata
        'meta_title',
        'meta_description',
        'meta_keywords',
        'slug',
        'canonical_url',
        'og_title',
        'og_description',

        // AI flags
        'ai_generated',
        'ai_improve',
    ];

    protected function casts(): array
    {
        return [
            'options'            => 'array',
            'correct_answers'    => 'array',
            'allows_multiple'    => 'boolean',
            'updated_by_history' => 'array',
            'marks_list'         => 'array',
            'ai_generated'       => 'boolean',
            'ai_improve'         => 'boolean',
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

    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'exam_question')
            ->withPivot(['sort_order', 'marks_override', 'status'])
            ->withTimestamps();
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForOrg($query, int $orgId)
    {
        return $query->where('organization_id', $orgId);
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }
}
