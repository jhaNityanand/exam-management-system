<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SitemapLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'run_at',
        'total_records_processed',
        'total_urls_generated',
        'processing_time_ms',
        'errors',
        'generated_urls',
        'status',
        'notes',
    ];

    protected $casts = [
        'run_at' => 'datetime',
        'total_records_processed' => 'integer',
        'total_urls_generated' => 'integer',
        'processing_time_ms' => 'float',
        'generated_urls' => 'array',
    ];
}
