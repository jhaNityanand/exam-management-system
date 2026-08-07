<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LlmErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'account_id',
        'account_name',
        'model',
        'request_type',
        'error_message',
        'error_code',
        'http_status',
        'response_body',
        'retry_count',
        'failed_at',
    ];

    protected $casts = [
        'account_id' => 'integer',
        'http_status' => 'integer',
        'retry_count' => 'integer',
        'failed_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(LlmAccount::class, 'account_id');
    }
}
