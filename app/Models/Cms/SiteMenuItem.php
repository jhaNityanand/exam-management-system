<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteMenuItem extends Model
{
    protected $table = 'site_menu_items';

    protected $fillable = [
        'menu_id',
        'parent_id',
        'label',
        'type',
        'route_name',
        'url',
        'page_slug',
        'icon',
        'target',
        'sort_order',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(SiteMenu::class, 'menu_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public function href(): string
    {
        return match ($this->type) {
            'route' => $this->route_name && \Illuminate\Support\Facades\Route::has($this->route_name)
                ? route($this->route_name)
                : '#',
            'page' => $this->page_slug
                ? route('frontend.pages.show', $this->page_slug)
                : '#',
            default => $this->url ?: '#',
        };
    }
}
