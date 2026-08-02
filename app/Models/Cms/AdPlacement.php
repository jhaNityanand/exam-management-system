<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Organization;
use App\Support\AdvertisementCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdPlacement extends Model
{
    use BelongsToOrganization, SoftDeletes;

    protected $table = 'ad_placements';

    protected $fillable = [
        'organization_id',
        'page_key',
        'position_key',
        'source_type',
        'advertisement_id',
        'google_advertisement_id',
        'sort_order',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function advertisement(): BelongsTo
    {
        return $this->belongsTo(Advertisement::class);
    }

    public function googleAdvertisement(): BelongsTo
    {
        return $this->belongsTo(GoogleAdvertisement::class);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForPage(Builder $query, string $pageKey): Builder
    {
        return $query->where('page_key', $pageKey);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function isGoogle(): bool
    {
        return $this->source_type === AdvertisementCatalog::SOURCE_GOOGLE;
    }

    public function isCustom(): bool
    {
        return $this->source_type === AdvertisementCatalog::SOURCE_CUSTOM;
    }

    public function pageLabel(): string
    {
        return AdvertisementCatalog::page($this->page_key)['label'] ?? $this->page_key;
    }

    public function positionLabel(): string
    {
        return AdvertisementCatalog::positions()[$this->position_key]['label'] ?? $this->position_key;
    }

    public function linkedName(): string
    {
        if ($this->isGoogle()) {
            return $this->googleAdvertisement?->name ?? 'Google Ad #'.$this->google_advertisement_id;
        }

        return $this->advertisement?->displayTitle() ?? 'Advertisement #'.$this->advertisement_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'page_key' => $this->page_key,
            'page_label' => $this->pageLabel(),
            'position_key' => $this->position_key,
            'position_label' => $this->positionLabel(),
            'source_type' => $this->source_type,
            'advertisement_id' => $this->advertisement_id,
            'google_advertisement_id' => $this->google_advertisement_id,
            'sort_order' => $this->sort_order,
            'is_enabled' => (bool) $this->is_enabled,
            'name' => $this->linkedName(),
            'preview_label' => $this->isGoogle()
                ? ($this->googleAdvertisement?->toAdminArray()['preview_label'] ?? 'Google Ad')
                : ($this->advertisement?->previewLabel() ?? 'Custom Ad'),
            'ad' => $this->isCustom() ? $this->advertisement?->toAdminArray() : null,
            'google_ad' => $this->isGoogle() ? $this->googleAdvertisement?->toAdminArray() : null,
        ];
    }
}
