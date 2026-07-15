<?php

namespace App\Models\Cms;

use App\Models\Blog;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogComment extends Model
{
    use BelongsToOrganization, SoftDeletes;

    protected $table = 'blog_comments';

    protected $fillable = [
        'organization_id',
        'blog_id',
        'user_id',
        'author_name',
        'author_email',
        'body',
        'status',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
