<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NewsTag extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $table = 'news_tags';

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function news(): BelongsToMany
    {
        return $this->belongsToMany(News::class, 'news_tag_relations', 'tag_id', 'news_id')
            ->withTimestamps();
    }
}
