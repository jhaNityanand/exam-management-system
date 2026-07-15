<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Gallery;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use BelongsToOrganization, SoftDeletes;

    protected $table = 'partners';

    protected $fillable = [
        'organization_id',
        'name',
        'url',
        'logo_id',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(Gallery::class, 'logo_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
