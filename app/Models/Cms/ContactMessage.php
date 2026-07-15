<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    use BelongsToOrganization;

    protected $table = 'contact_messages';

    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
