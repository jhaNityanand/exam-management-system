<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamPart extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'exam_id',
        'name',
        'sort_order',
        'is_default',
        'total_questions',
        'total_marks',
        'use_question_pool',
        'maximum_questions',
        'fixed_questions',
        'fixed_paper_set',
        'paper_sets',
        'fix_category_questions',
        'fix_category_marks',
        'distribution_type',
        'fix_marks_each_question',
        'selected_categories',
        'extra_questions_categories',
        'extra_questions_allocations',
        'extra_marks_allocations',
        'question_marks_filter',
        'category_question_rules',
        'shuffle_questions',
        'shuffle_categories',
        'shuffle_options',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'use_question_pool' => 'boolean',
            'fixed_questions' => 'boolean',
            'fixed_paper_set' => 'boolean',
            'fix_category_questions' => 'boolean',
            'fix_category_marks' => 'boolean',
            'fix_marks_each_question' => 'boolean',
            'shuffle_questions' => 'boolean',
            'shuffle_categories' => 'boolean',
            'shuffle_options' => 'boolean',
            'selected_categories' => 'array',
            'extra_questions_categories' => 'array',
            'extra_questions_allocations' => 'array',
            'extra_marks_allocations' => 'array',
            'question_marks_filter' => 'array',
            'category_question_rules' => 'array',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function selectedQuestionCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            QuestionCategory::class,
            'exam_part_question_category',
            'exam_part_id',
            'question_category_id'
        );
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_part_question')
            ->withPivot(['sort_order', 'marks_override', 'status'])
            ->withTimestamps();
    }
}
