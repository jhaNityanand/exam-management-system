<?php

namespace App\Models;

use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasAuditTrails, HasFactory, SoftDeletes;

    protected $fillable = [
        // Identity
        'user_id',
        'name',
        'slug',
        'description',
        'logo',
        'banner',
        'status',

        // SEO / Metadata
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',

        // Audit
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

    // ── Relationships ─────────────────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_organizations')
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    public function examCategories()
    {
        return $this->hasMany(ExamCategory::class);
    }

    public function questionCategories()
    {
        return $this->hasMany(QuestionCategory::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function blogCategories()
    {
        return $this->hasMany(BlogCategory::class);
    }

    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }

    public function newsCategories()
    {
        return $this->hasMany(NewsCategory::class);
    }

    public function news()
    {
        return $this->hasMany(News::class);
    }

    public function galleries()
    {
        return $this->hasMany(Gallery::class);
    }
}
