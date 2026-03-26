<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditTrails;

class Profile extends Model
{
    use HasAuditTrails, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'status',
        'created_by',
        'updated_by',
        'bio',
        'phone',
        'avatar',
        'address_line1',
        'address_line2',
        'city',
        'state_region',
        'postal_code',
        'country',
        'default_organization_id',
        'social_links',
        'updated_by_history',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'updated_by_history' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class , 'id', 'id');
    }

    public function defaultOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class , 'default_organization_id');
    }
}
