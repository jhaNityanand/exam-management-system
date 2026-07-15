<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use BelongsToOrganization, SoftDeletes;

    protected $table = 'announcements';

    protected $fillable = [
        'organization_id',
        'title',
        'message',
        'type',
        'cta_label',
        'cta_url',
        'is_dismissible',
        'sort_order',
        'status',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_dismissible' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
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
