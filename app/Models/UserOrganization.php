<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditTrails;

class UserOrganization extends Model
{
    use HasAuditTrails, SoftDeletes;

    protected $table = 'user_organizations';

    protected $fillable = [
        'user_id',
        'organization_id',
        'role',
        'status',
        'created_by',
        'updated_by',
        'updated_by_history',
    ];

    protected function casts(): array
    {
        return [
            'updated_by_history' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
