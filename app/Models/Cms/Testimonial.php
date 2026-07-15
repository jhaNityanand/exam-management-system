<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Gallery;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Testimonial extends Model
{
    use BelongsToOrganization, SoftDeletes;

    protected $table = 'testimonials';

    protected $fillable = [
        'organization_id',
        'name',
        'role',
        'company',
        'quote',
        'rating',
        'avatar_id',
        'is_featured',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Gallery::class, 'avatar_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
