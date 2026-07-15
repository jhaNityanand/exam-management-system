<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteMenu extends Model
{
    use BelongsToOrganization;

    protected $table = 'site_menus';

    protected $fillable = [
        'organization_id',
        'name',
        'location',
        'status',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SiteMenuItem::class, 'menu_id')->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
