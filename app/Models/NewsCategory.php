<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * NewsCategory
 *
 * Hierarchical category for news items, scoped to an organization.
 */
class NewsCategory extends Model
{
    use BelongsToOrganization, HasAuditTrails, HasFactory, SoftDeletes;

    protected $table = 'news_categories';

    protected $fillable = [
        'organization_id',
        'parent_id',
        'created_by',
        'updated_by',
        'updated_by_history',
        'name',
        'description',
        'status',
        'sort_order',
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
        'ai_generated',
        'ai_improve',
    ];

    protected function casts(): array
    {
        return [
            'updated_by_history' => 'array',
            'ai_generated' => 'boolean',
            'ai_improve' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (NewsCategory $category) {
            if ($category->isForceDeleting()) {
                return;
            }
            foreach ($category->children()->get() as $child) {
                $child->delete();
            }
            $category->news()->delete();
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Gallery::class, 'og_image_id');
    }

    public function parent()
    {
        return $this->belongsTo(NewsCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(NewsCategory::class, 'parent_id');
    }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    public function news()
    {
        return $this->hasMany(News::class, 'news_category_id');
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
