<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Blog extends Model
{
    use BelongsToOrganization, HasAuditTrails, HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'organization_id',
        'blog_category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'banner_image_id',
        'author_id',
        'author_name',
        'status',
        'published_at',
        'view_count',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'og_title',
        'og_description',
        'og_image_id',
        'canonical_url',
        'robots',
        'schema_markup',
        'ai_generated',
        'ai_improve',
        'created_by',
        'updated_by',
        'updated_by_history',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'view_count' => 'integer',
            'ai_generated' => 'boolean',
            'ai_improve' => 'boolean',
            'updated_by_history' => 'array',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_REVIEW => 'Pending Review',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function bannerImage(): BelongsTo
    {
        return $this->belongsTo(Gallery::class, 'banner_image_id');
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Gallery::class, 'og_image_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_tag_relations', 'blog_id', 'tag_id')
            ->withTimestamps();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(BlogAttachment::class);
    }

    public function galleryAttachments(): BelongsToMany
    {
        return $this->belongsToMany(Gallery::class, 'blog_attachments', 'blog_id', 'gallery_id')
            ->withTimestamps();
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function bannerUrl(): ?string
    {
        return $this->bannerImage?->file_url;
    }
}
