<?php

namespace App\Models;

use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasAuditTrails, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'category_id',
        'created_by',
        'updated_by',
        'updated_by_history',
        'status',
        'body',
        'type',
        'allows_multiple',
        'options',
        'correct_answer',
        'correct_answers',
        'explanation',
        'marks',
        'difficulty',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_answers' => 'array',
            'allows_multiple' => 'boolean',
            'updated_by_history' => 'array',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'exam_question')
            ->withPivot(['sort_order', 'marks_override', 'status'])
            ->withTimestamps();
    }

    public function scopeForOrg($query, int $orgId)
    {
        return $query->where('organization_id', $orgId);
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }
}
