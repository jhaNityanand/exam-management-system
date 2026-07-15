<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSetting extends Model
{
    use BelongsToOrganization;

    protected $table = 'site_settings';

    protected $fillable = [
        'organization_id',
        'group',
        'key',
        'value',
        'type',
        'label',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeForGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }
}
