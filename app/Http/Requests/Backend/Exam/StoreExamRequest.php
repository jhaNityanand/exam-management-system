<?php

namespace App\Http\Requests\Backend\Exam;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreExamRequest
 *
 * Validates all fields submitted from the exam creation form.
 * Mirrors the exam-create.js multi-step wizard exactly.
 */
class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // ── Section 1: Basic Information ──────────────────────────────
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'exam_category_id' => ['nullable', 'integer', Rule::exists('exam_categories', 'id')],
            'category_id'      => ['nullable', 'integer', Rule::exists('exam_categories', 'id')],
            'difficulty_level' => ['nullable', Rule::in(['easy', 'medium', 'hard'])],
            'status'           => ['required', Rule::in(['draft', 'published', 'active', 'inactive', 'suspended'])],
            'exam_mode'        => ['required', Rule::in(['standard', 'practice', 'proctored'])],
            'visibility'       => ['required', Rule::in(['public', 'private', 'invite_only'])],
            'tags'             => ['nullable', 'json'],

            // ── Section 2: Timer & Duration ───────────────────────────────
            'enable_exam_timer'        => ['sometimes', 'boolean'],
            'exam_duration_minutes'    => ['required', 'integer', 'min:1', 'max:999'],
            'auto_submit_on_timer_end' => ['sometimes', 'boolean'],

            // ── Section 3: Exam Format ────────────────────────────────────
            'exam_format'   => ['required', 'array', 'min:1'],
            'exam_format.*' => [Rule::in(\App\Support\ExamFormOptions::examFormatIds())],

            // ── Section 4: Schedule & Attempts ───────────────────────────
            'schedule_type'       => ['required', Rule::in(['any_time', 'fixed_window'])],
            'schedule_start_at'   => ['required_if:schedule_type,fixed_window', 'nullable', 'date'],
            'schedule_end_at'     => ['required_if:schedule_type,fixed_window', 'nullable', 'date', 'after:schedule_start_at'],
            'attempt_limit_type'  => ['required', Rule::in(['once', 'fixed', 'unlimited'])],
            'attempt_limit_count' => ['required_if:attempt_limit_type,fixed', 'nullable', 'integer', 'min:2'],

            // ── Section 5: Candidate Access ───────────────────────────────
            'imported_candidates'    => ['nullable', 'json'],
            'manual_candidate_emails'=> ['nullable', 'json'],

            // ── Section 6: Exam Configuration ─────────────────────────────
            'total_questions'              => ['required', 'integer', 'min:1'],
            'total_categories'             => ['required', 'integer', 'min:1'],
            'total_marks'                  => ['required', 'integer', 'min:1'],
            'passing_marks'                => ['required', 'integer', 'min:0'],
            'paper_sets'                   => ['required', 'integer', 'min:1'],
            'fix_category_questions'       => ['sometimes', 'boolean'],
            'distribution_type'            => ['nullable', Rule::in(['mixed', 'category_wise', 'equal', 'weighted', 'manual'])],
            'selected_categories'          => ['nullable', 'json'],
            'extra_questions_categories'   => ['nullable', 'json'],
            'extra_questions_allocations'  => ['nullable', 'json'],

            // ── Section 7: Question Rules & Filters ───────────────────────
            'fix_marks_each_question'  => ['sometimes', 'boolean'],
            'enable_negative_marking'  => ['sometimes', 'boolean'],
            'negative_marking_type'    => ['nullable', 'string', 'max:10'],
            'negative_mark_per_question' => ['nullable', 'numeric', 'min:0'],
            'question_marks_filter'    => ['nullable', 'json'],
            'shuffle_questions'        => ['sometimes', 'boolean'],
            'shuffle_options'          => ['sometimes', 'boolean'],
            'category_question_rules'  => ['nullable', 'json'],

            // ── Section 9: Instructions (mapped from 'instructions' field) ─
            'instructions'             => ['nullable', 'string'],

            // ── SEO / Metadata ────────────────────────────────────────────
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords'    => ['nullable', 'string', 'max:500'],
            'slug'             => ['nullable', 'string', 'max:255'],
            'canonical_url'    => ['nullable', 'url', 'max:500'],
            'og_title'         => ['nullable', 'string', 'max:255'],
            'og_description'   => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'                => 'Please enter an exam title.',
            'status.required'               => 'Please select a status.',
            'exam_mode.required'            => 'Please select an exam mode.',
            'visibility.required'           => 'Please select a visibility option.',
            'exam_format.required'          => 'Please select at least one exam format.',
            'schedule_type.required'        => 'Please select a schedule type.',
            'schedule_start_at.required_if' => 'Please set a start date for the fixed window.',
            'schedule_end_at.required_if'   => 'Please set an end date for the fixed window.',
            'schedule_end_at.after'         => 'End date must be after the start date.',
            'attempt_limit_type.required'   => 'Please select an attempt limit type.',
            'attempt_limit_count.required_if' => 'Please enter the maximum number of attempts.',
            'total_questions.required'      => 'Total questions is required.',
            'total_categories.required'     => 'Total categories is required.',
            'total_marks.required'          => 'Total marks is required.',
            'passing_marks.required'        => 'Passing marks is required.',
            'paper_sets.required'           => 'Number of paper sets is required.',
            'exam_duration_minutes.required'=> 'Please enter the exam duration.',
        ];
    }

    /**
     * Prepare the data before validation — normalise checkboxes, map field names.
     */
    protected function prepareForValidation(): void
    {
        $examFormat = $this->input('exam_format');
        if (is_string($examFormat) && str_starts_with(trim($examFormat), '[')) {
            $examFormat = json_decode($examFormat, true);
        }

        $this->merge([
            // Map the form field name to the model's column name
            'duration'                 => $this->input('exam_duration_minutes'),
            'scheduled_start'          => $this->input('schedule_start_at') ?: null,
            'scheduled_end'            => $this->input('schedule_end_at') ?: null,
            'max_attempts'             => $this->input('attempt_limit_count', 1),

            // Normalise checkboxes (absent = false)
            'enable_exam_timer'        => (bool) $this->input('enable_exam_timer', false),
            'auto_submit_on_timer_end' => (bool) $this->input('auto_submit_on_timer_end', false),
            'shuffle_questions'        => (bool) $this->input('shuffle_questions', false),
            'shuffle_options'          => (bool) $this->input('shuffle_options', false),
            'enable_negative_marking'  => (bool) $this->input('enable_negative_marking', false),
            'fix_marks_each_question'  => (bool) $this->input('fix_marks_each_question', false),
            'fix_category_questions'   => (bool) $this->input('fix_category_questions', false),

            // Map exam_category_id → category_id (create form uses exam_category_id field name)
            'category_id'              => $this->input('exam_category_id') ?: $this->input('category_id'),
            'exam_format'              => $examFormat,
            // Normalize UI id fixed_count → stored validation id fixed
            'attempt_limit_type'       => $this->input('attempt_limit_type') === 'fixed_count'
                ? 'fixed'
                : $this->input('attempt_limit_type'),
        ]);
    }
}