<?php

namespace App\Models;

use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ExamCategory
 *
 * Represents a hierarchical category for exams. Categories can be nested
 * (parent → children) to arbitrary depth and are scoped to an organization.
 */
class ExamCategory extends Model
{
    use HasAuditTrails, HasFactory, SoftDeletes;

    protected $table = 'exam_categories';

    protected $fillable = [
        // Relations & Audit
        'organization_id',
        'parent_id',
        'created_by',
        'updated_by',
        'updated_by_history',

        // Content
        'name',
        'description',
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
            'updated_by_history' => 'array',
            'ai_generated'       => 'boolean',
            'ai_improve'         => 'boolean',
        ];
    }

    // ── Lifecycle Hooks ───────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::deleting(function (ExamCategory $category) {
            // Cascade soft-delete to children and exams on soft-delete
            if ($category->isForceDeleting()) {
                return;
            }
            foreach ($category->children()->get() as $child) {
                $child->delete();
            }
            $category->exams()->delete();
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function parent()
    {
        return $this->belongsTo(ExamCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ExamCategory::class, 'parent_id');
    }

    /**
     * All descendants loaded recursively.
     */
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'category_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** Only top-level categories (no parent). */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /** Filter to a specific organization. */
    public function scopeForOrg($query, int $orgId)
    {
        return $query->where('organization_id', $orgId);
    }

    /** Filter by status. */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
