<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Gallery;
use App\Models\Organization;
use App\Support\AdvertisementCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Advertisement extends Model
{
    use BelongsToOrganization, SoftDeletes;

    protected $table = 'advertisements';

    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'placement',
        'headline',
        'body',
        'code',
        'cta_label',
        'cta_url',
        'image_id',
        'mobile_image_id',
        'sort_order',
        'status',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
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

    public function scopeActive(Builder $query, ?string $placement = null): Builder
    {
        $query->where('status', 'active')
            ->where(function (Builder $window) {
                $window->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $window) {
                $window->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });

        if ($placement !== null) {
            $query->where('placement', $placement);
        }

        return $query;
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function typeLabel(): string
    {
        return AdvertisementCatalog::types()[$this->type] ?? ucfirst((string) $this->type);
    }

    public function placementLabel(): string
    {
        return AdvertisementCatalog::placements()[$this->placement] ?? (string) $this->placement;
    }

    public function isBanner(): bool
    {
        return $this->type === AdvertisementCatalog::TYPE_BANNER;
    }

    public function usesCode(): bool
    {
        return in_array($this->type, [
            AdvertisementCatalog::TYPE_GOOGLE_ADS,
            AdvertisementCatalog::TYPE_CUSTOM_HTML,
            AdvertisementCatalog::TYPE_IFRAME,
        ], true);
    }
}
