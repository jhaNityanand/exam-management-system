<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAppSetting extends Model
{
    protected $fillable = [
        'user_id',
        'theme',
        'sidebar_collapsed',
        'preferences',
    ];

    protected function casts(): array
    {
        return [
            'sidebar_collapsed' => 'boolean',
            'preferences' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
