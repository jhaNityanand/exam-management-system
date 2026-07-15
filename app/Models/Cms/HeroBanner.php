<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Gallery;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HeroBanner extends Model
{
    use BelongsToOrganization, SoftDeletes;

    protected $table = 'hero_banners';

    protected $fillable = [
        'organization_id',
        'title',
        'subtitle',
        'description',
        'badge_text',
        'primary_cta_label',
        'primary_cta_url',
        'secondary_cta_label',
        'secondary_cta_url',
        'image_id',
        'mobile_image_id',
        'theme',
        'show_search',
        'sort_order',
        'status',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'show_search' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Gallery::class, 'image_id');
    }

    public function mobileImage(): BelongsTo
    {
        return $this->belongsTo(Gallery::class, 'mobile_image_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function (Builder $window) {
                $window->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $window) {
                $window->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
