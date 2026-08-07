<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoProcessingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'run_at',
        'seo_type',
        'processed_records_count',
        'successful_count',
        'failed_count',
        'provider_used',
        'account_used',
        'execution_time_ms',
        'api_tokens_used',
        'error_summary',
        'processed_record_ids',
    ];

    protected $casts = [
        'run_at' => 'datetime',
        'processed_records_count' => 'integer',
        'successful_count' => 'integer',
        'failed_count' => 'integer',
        'execution_time_ms' => 'float',
        'api_tokens_used' => 'integer',
        'processed_record_ids' => 'array',
    ];
}
