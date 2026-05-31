<?php

namespace App\Models;

use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasAuditTrails, HasFactory, SoftDeletes;

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
    ];

    protected function casts(): array
    {
        return [
            'updated_by_history' => 'array',
        ];
    }

    // ── Lifecycle Hooks ───────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::deleting(function (Category $category) {
            if ($category->isForceDeleting()) {
                return;
            }
            foreach ($category->children()->get() as $child) {
                $child->delete();
            }
            $category->questions()->delete();
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeForOrg($query, int $orgId)
    {
        return $query->where('organization_id', $orgId);
    }
}
