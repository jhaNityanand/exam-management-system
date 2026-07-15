<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Gallery;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SitePage extends Model
{
    use BelongsToOrganization, SoftDeletes;

    protected $table = 'site_pages';

    protected $fillable = [
        'organization_id',
        'title',
        'slug',
        'template',
        'excerpt',
        'content',
        'banner_image_id',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'status',
        'published_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function bannerImage(): BelongsTo
    {
        return $this->belongsTo(Gallery::class, 'banner_image_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
