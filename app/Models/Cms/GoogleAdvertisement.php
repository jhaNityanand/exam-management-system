<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoogleAdvertisement extends Model
{
    use BelongsToOrganization, SoftDeletes;

    protected $table = 'google_advertisements';

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'ad_client',
        'ad_slot',
        'ad_format',
        'notes',
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

    public function placements(): HasMany
    {
        return $this->hasMany(AdPlacement::class, 'google_advertisement_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'ad_client' => $this->ad_client,
            'ad_slot' => $this->ad_slot,
            'ad_format' => $this->ad_format,
            'notes' => $this->notes,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'preview_label' => 'Google Ad'.($this->ad_format ? ' · '.$this->ad_format : ''),
        ];
    }
}
