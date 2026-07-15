<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Gallery;
use App\Models\Organization;
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
        'placement',
        'headline',
        'body',
        'cta_label',
        'cta_url',
        'image_id',
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
        return $query->orderBy('sort_order');
    }
}
