<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Gallery;
use App\Models\Organization;
use App\Support\AdvertisementCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Advertisement extends Model
{
    use BelongsToOrganization, SoftDeletes;

    protected $table = 'advertisements';

    protected $fillable = [
        'organization_id',
        'name',
        'title',
        'type',
        'image_id',
        'target_url',
        'open_in_new_tab',
        'banner_size',
        'iframe_url',
        'width',
        'height',
        'is_responsive',
        'html_code',
        'css_code',
        'js_code',
        'notes',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'boolean',
            'is_responsive' => 'boolean',
            'width' => 'integer',
            'height' => 'integer',
            'sort_order' => 'integer',
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

    public function placements(): HasMany
    {
        return $this->hasMany(AdPlacement::class, 'advertisement_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function typeLabel(): string
    {
        return AdvertisementCatalog::types()[$this->type] ?? ucfirst((string) $this->type);
    }

    public function displayTitle(): string
    {
        return (string) ($this->title ?: $this->name);
    }

    public function bannerSizeLabel(): ?string
    {
        if (! $this->banner_size) {
            return null;
        }
        $size = AdvertisementCatalog::bannerSizes()[$this->banner_size] ?? null;
        if (! $size) {
            return $this->banner_size;
        }

        return $size['label'].' ('.$size['width'].'×'.$size['height'].')';
    }

    public function isBanner(): bool
    {
        return $this->type === AdvertisementCatalog::TYPE_BANNER;
    }

    public function isIframe(): bool
    {
        return $this->type === AdvertisementCatalog::TYPE_IFRAME;
    }

    public function isHtml(): bool
    {
        return $this->type === AdvertisementCatalog::TYPE_HTML;
    }

    /**
     * Compact payload for admin AJAX / placement picker.
     *
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->displayTitle(),
            'type' => $this->type,
            'type_label' => $this->typeLabel(),
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'image_id' => $this->image_id,
            'image_url' => $this->image?->file_url,
            'target_url' => $this->target_url,
            'open_in_new_tab' => (bool) $this->open_in_new_tab,
            'banner_size' => $this->banner_size,
            'banner_size_label' => $this->bannerSizeLabel(),
            'iframe_url' => $this->iframe_url,
            'width' => $this->width,
            'height' => $this->height,
            'is_responsive' => (bool) $this->is_responsive,
            'html_code' => $this->html_code,
            'css_code' => $this->css_code,
            'js_code' => $this->js_code,
            'notes' => $this->notes,
            'preview_label' => $this->previewLabel(),
        ];
    }

    public function previewLabel(): string
    {
        return match ($this->type) {
            AdvertisementCatalog::TYPE_BANNER => $this->bannerSizeLabel() ?: 'Banner',
            AdvertisementCatalog::TYPE_IFRAME => 'Iframe'.($this->width && $this->height ? " {$this->width}×{$this->height}" : ''),
            AdvertisementCatalog::TYPE_HTML => 'HTML / CSS / JS',
            default => $this->typeLabel(),
        };
    }
}
