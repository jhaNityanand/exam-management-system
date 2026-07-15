<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'news_id',
        'gallery_id',
    ];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class, 'gallery_id');
    }
}
