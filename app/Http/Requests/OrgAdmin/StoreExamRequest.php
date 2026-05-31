<?php

namespace App\Http\Requests\OrgAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // Identity
            'title'                       => ['required', 'string', 'max:255'],
            'description'                 => ['nullable', 'string'],
            'category_id'                 => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'status'                      => ['sometimes', Rule::in(['draft', 'published', 'active', 'inactive', 'suspended'])],
            'exam_mode'                   => ['nullable', 'string', Rule::in(['standard', 'practice', 'proctored'])],
            'exam_format'                 => ['nullable', 'string', Rule::in(['mcq', 'written', 'multi_select', 'mixed'])],
            'difficulty_level'            => ['nullable', 'string', Rule::in(['easy', 'medium', 'hard'])],
            'visibility'                  => ['nullable', 'string', Rule::in(['public', 'private', 'invite_only'])],
            'tags'                        => ['nullable', 'string'],

            // Timer & Duration
            'duration'                    => ['required', 'integer', 'min:1', 'max:480'],
            'enable_exam_timer'           => ['sometimes', 'boolean'],
            'auto_submit_on_timer_end'    => ['sometimes', 'boolean'],

            // Scheduling
            'schedule_type'               => ['nullable', 'string', Rule::in(['any_time', 'fixed_window'])],
            'scheduled_start'             => ['nullable', 'date'],
            'scheduled_end'               => ['nullable', 'date', 'after_or_equal:scheduled_start'],

            // Attempts
            'attempt_limit_type'          => ['nullable', 'string', Rule::in(['once', 'fixed', 'unlimited'])],
            'max_attempts'                => ['required', 'integer', 'min:1', 'max:50'],

            // Scoring
            'pass_percentage'             => ['required', 'numeric', 'min:0', 'max:100'],
            'total_marks'                 => ['nullable', 'integer', 'min:1'],
            'passing_marks'               => ['nullable', 'integer', 'min:0'],
            'negative_mark_per_question'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'enable_negative_marking'     => ['sometimes', 'boolean'],
            'negative_marking_type'       => ['nullable', 'string'],
            'fix_marks_each_question'     => ['sometimes', 'boolean'],

            // Question Configuration
            'total_questions'             => ['nullable', 'integer', 'min:1'],
            'total_categories'            => ['nullable', 'integer', 'min:1'],
            'paper_sets'                  => ['nullable', 'integer', 'min:1'],
            'fix_category_questions'      => ['sometimes', 'boolean'],
            'distribution_type'           => ['nullable', 'string'],
            'selected_categories'         => ['nullable', 'string'],
            'extra_questions_categories'  => ['nullable', 'string'],
            'extra_questions_allocations' => ['nullable', 'string'],
            'question_marks_filter'       => ['nullable', 'string'],
            'category_question_rules'     => ['nullable', 'array'],

            // Shuffle
            'shuffle_questions'           => ['sometimes', 'boolean'],
            'shuffle_options'             => ['sometimes', 'boolean'],

            // Candidate Access
            'imported_candidates'         => ['nullable', 'string'],
            'manual_candidate_emails'     => ['nullable', 'string'],

            // Questions
            'question_ids'                => ['nullable', 'array'],
            'question_ids.*'              => ['integer', Rule::exists('questions', 'id')],

            // SEO / Metadata
            'meta_title'                  => ['nullable', 'string', 'max:255'],
            'meta_description'            => ['nullable', 'string', 'max:500'],
            'meta_keywords'               => ['nullable', 'string', 'max:500'],
            'slug'                        => ['nullable', 'string', 'max:255'],
            'canonical_url'               => ['nullable', 'url', 'max:500'],
            'og_title'                    => ['nullable', 'string', 'max:255'],
            'og_description'              => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'    => 'Give this exam a title.',
            'duration.required' => 'Set a duration for the exam.',
        ];
    }
}
