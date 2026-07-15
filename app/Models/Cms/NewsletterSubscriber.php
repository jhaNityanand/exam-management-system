<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterSubscriber extends Model
{
    use BelongsToOrganization;

    protected $table = 'newsletter_subscribers';

    protected $fillable = [
        'organization_id',
        'email',
        'name',
        'status',
        'source',
        'subscribed_at',
        'unsubscribed_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeSubscribed(Builder $query): Builder
    {
        return $query->where('status', 'subscribed');
    }
}
