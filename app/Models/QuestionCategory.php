<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * QuestionCategory
 *
 * Represents a hierarchical category for questions. Categories can be nested
 * (parent → children) to arbitrary depth and are scoped to an organization.
 *
 * @property int $id
 * @property int $organization_id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $description
 * @property string $status active | inactive | suspended
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property string|null $slug
 * @property string|null $canonical_url
 * @property string|null $og_title
 * @property string|null $og_description
 * @property bool $ai_generated Create With AI (queue SEO generation)
 * @property bool $ai_improve Improve With AI (queue SEO improvement)
 * @property bool $is_ai_generated SEO already processed by AI
 * @property bool $is_sitemap_url_created URL already written to sitemap
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property array|null $updated_by_history
 */
class QuestionCategory extends Model
{
    use BelongsToOrganization, Concerns\HasAiSeo, HasAuditTrails, HasFactory, SoftDeletes;

    protected $table = 'question_categories';

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
        'icon',
        'image_path',
        'status',
        'is_public',
        'sort_order',

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
        'is_ai_generated',
        'is_sitemap_url_created',
    ];

    protected function casts(): array
    {
        return [
            'updated_by_history' => 'array',
            'ai_generated' => 'boolean',
            'ai_improve' => 'boolean',
            'is_ai_generated' => 'boolean',
            'is_sitemap_url_created' => 'boolean',
            'sort_order' => 'integer',
            'is_public' => 'boolean',
        ];
    }

    // ── Lifecycle Hooks ───────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::deleting(function (QuestionCategory $category) {
            // Cascade soft-delete to children and questions on soft-delete
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

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Gallery::class, 'og_image_id');
    }

    public function parent()
    {
        return $this->belongsTo(QuestionCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(QuestionCategory::class, 'parent_id');
    }

    /**
     * All descendants loaded recursively (eager-loadable up to 3 levels).
     */
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'category_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** Only top-level categories (no parent). */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /** Filter to a specific organization. */

    /** Filter by status. */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePubliclyVisible($query)
    {
        return $query->where('status', 'active')->where('is_public', true);
    }

    public function publicQuestions()
    {
        return $this->questions()->publiclyVisible();
    }

    public function socialImageUrl(): ?string
    {
        return $this->ogImage?->file_url;
    }

    public function seoImageUrl(): string
    {
        return seo_image($this->socialImageUrl(), 'category');
    }
}
