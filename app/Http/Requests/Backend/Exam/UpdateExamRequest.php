<?php

namespace App\Http\Requests\Backend\Exam;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateExamRequest
 *
 * Validates fields from the exam edit form (DB column names) while still
 * accepting create-wizard aliases for shared tooling.
 */
class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // ── Section 1: Basic Information ──────────────────────────────
            'title'            => ['sometimes', 'required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'exam_category_id' => [
                'nullable',
                'integer',
                Rule::exists('exam_categories', 'id')->where(function ($query) {
                    $orgId = current_organization_id();
                    if ($orgId) {
                        $query->where('organization_id', $orgId);
                    }
                }),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('exam_categories', 'id')->where(function ($query) {
                    $orgId = current_organization_id();
                    if ($orgId) {
                        $query->where('organization_id', $orgId);
                    }
                }),
            ],
            'difficulty_level' => ['nullable', Rule::in(['easy', 'medium', 'hard'])],
            'status'           => ['sometimes', 'required', Rule::in(['draft', 'published', 'active', 'inactive', 'suspended'])],
            'exam_mode'        => ['sometimes', 'required', Rule::in(['standard', 'practice', 'proctored'])],
            'visibility'       => ['sometimes', 'required', Rule::in(['public', 'private', 'invite_only'])],
            'tags'             => ['nullable'],

            // ── Section 2: Timer & Duration ───────────────────────────────
            'enable_exam_timer'        => ['sometimes', 'boolean'],
            'duration'                 => ['sometimes', 'required', 'integer', 'min:1', 'max:999'],
            'exam_duration_minutes'    => ['sometimes', 'integer', 'min:1', 'max:999'],
            'auto_submit_on_timer_end' => ['sometimes', 'boolean'],

            // ── Section 3: Exam Format ────────────────────────────────────
            'exam_format'   => ['sometimes', 'required', 'array', 'min:1'],
            'exam_format.*' => [Rule::in(\App\Support\ExamFormOptions::examFormatIds())],

            // ── Section 4: Schedule & Attempts ───────────────────────────
            'schedule_type'       => ['sometimes', 'required', Rule::in(['any_time', 'fixed_window'])],
            'scheduled_start'     => ['nullable', 'date'],
            'scheduled_end'       => ['nullable', 'date', 'after:scheduled_start'],
            'schedule_start_at'   => ['nullable', 'date'],
            'schedule_end_at'     => ['nullable', 'date', 'after:schedule_start_at'],
            'attempt_limit_type'  => ['sometimes', 'required', Rule::in(['once', 'fixed', 'unlimited'])],
            'max_attempts'        => ['nullable', 'integer', 'min:0'],
            'attempt_limit_count' => ['nullable', 'integer', 'min:2'],
            'pass_percentage'     => ['nullable', 'numeric', 'min:0', 'max:100'],

            // ── Section 5: Candidate Access ───────────────────────────────
            'imported_candidates'          => ['nullable'],
            'manual_candidate_emails'      => ['nullable'],
            'free_imported_candidates'     => ['nullable'],
            'free_manual_candidate_emails' => ['nullable'],

            // ── Section 6: Exam Configuration ─────────────────────────────
            'total_questions'              => ['sometimes', 'integer', 'min:1'],
            'total_marks'                  => ['sometimes', 'integer', 'min:1'],
            'passing_marks'                => ['sometimes', 'integer', 'min:0', 'lte:total_marks'],
            'use_question_pool'             => ['sometimes', 'boolean'],
            'maximum_questions'             => [
                Rule::requiredIf(fn () => $this->boolean('use_question_pool')),
                'nullable',
                'integer',
                'gt:total_questions',
                'max:65535',
            ],
            'fixed_questions'              => ['sometimes', 'boolean'],
            'fixed_paper_set'              => ['sometimes', 'boolean'],
            'paper_sets'                   => ['sometimes', 'integer', 'min:1', 'lte:total_questions'],
            'fix_category_questions'       => ['sometimes', 'boolean'],
            'fix_category_marks'           => ['sometimes', 'boolean'],
            'distribution_type'            => ['nullable', Rule::in(['mixed', 'category_wise', 'equal', 'weighted', 'manual'])],
            'selected_categories'          => ['sometimes', 'array', 'min:1'],
            'selected_categories.*'        => [
                'integer',
                Rule::exists('question_categories', 'id')->where(function ($query) {
                    $orgId = current_organization_id();
                    $query->where('status', 'active');
                    if ($orgId) {
                        $query->where('organization_id', $orgId);
                    }
                }),
            ],
            'extra_questions_categories'   => ['nullable'],
            'extra_questions_allocations'  => ['nullable'],
            'extra_marks_allocations'      => ['nullable', 'array'],
            'extra_marks_allocations.*'    => ['integer', 'min:0'],
            'question_ids'                 => ['nullable', 'array'],
            'question_ids.*'               => [
                'integer',
                Rule::exists('questions', 'id')->where(function ($query) {
                    $orgId = current_organization_id();
                    $query->where('status', 'active');
                    if ($orgId) {
                        $query->where('organization_id', $orgId);
                    }
                }),
            ],

            // ── Section 7: Question Rules & Filters ───────────────────────
            'fix_marks_each_question'    => ['sometimes', 'boolean'],
            'enable_negative_marking'    => ['sometimes', 'boolean'],
            'negative_marking_type'      => [
                'nullable',
                Rule::in(['25', '33.33', '50', '100']),
            ],
            'negative_mark_per_question' => ['nullable', 'numeric', 'min:0'],
            'question_marks_filter'      => ['sometimes', 'array', 'min:1'],
            'question_marks_filter.*'    => ['integer', 'min:1', 'max:10'],
            'shuffle_questions'          => ['sometimes', 'boolean'],
            'shuffle_categories'         => ['sometimes', 'boolean'],
            'shuffle_options'            => ['sometimes', 'boolean'],
            'category_question_rules'    => ['nullable', 'array'],
            'category_question_rules.*.category_id' => ['required_with:category_question_rules', 'integer'],
            'category_question_rules.*.marks' => ['required_with:category_question_rules', 'integer', 'min:1', 'max:10'],
            'category_question_rules.*.required' => ['required_with:category_question_rules', 'integer', 'min:1'],

            // ── Pricing & Discounts ───────────────────────────────────────
            'pricing_option'     => ['nullable', Rule::in(['paid', 'free', 'free_for_imported'])],
            'exam_currency'      => ['nullable', 'string', 'max:10'],
            'exam_amount'        => ['nullable', 'numeric', 'min:0'],
            'selected_discounts' => ['nullable'],
            'custom_discounts'   => ['nullable'],

            // ── Instructions ──────────────────────────────────────────────
            'instructions'                   => ['nullable', 'string'],
            'predefined_instruction_rules'   => ['nullable'],
            'predefined_instruction_rules.*' => [
                'string',
                Rule::in(\App\Support\ExamFormOptions::instructionRuleIds()),
            ],

            // ── SEO / Metadata ────────────────────────────────────────────
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords'    => ['nullable', 'string', 'max:500'],
            'slug'             => ['nullable', 'string', 'max:255'],
            'canonical_url'    => ['nullable', 'url', 'max:500'],
            'og_title'         => ['nullable', 'string', 'max:255'],
            'og_description'   => ['nullable', 'string', 'max:500'],
            'og_image_id'      => [
                'nullable',
                'integer',
                Rule::exists('galleries', 'id')->where(function ($query) {
                    $orgId = current_organization_id();
                    if ($orgId !== null) {
                        $query->where('organization_id', $orgId);
                    }
                }),
            ],
            'robots'           => ['nullable', 'string', 'max:255'],
            'schema_markup'    => ['nullable', 'string'],
            'ai_generated'     => ['sometimes', 'boolean'],
            'ai_improve'       => ['sometimes', 'boolean'],
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
            'scheduled_end.after'           => 'End date must be after the start date.',
            'schedule_end_at.after'         => 'End date must be after the start date.',
            'attempt_limit_type.required'   => 'Please select an attempt limit type.',
            'duration.required'             => 'Please enter the exam duration.',
            'exam_duration_minutes.required'=> 'Please enter the exam duration.',
        ];
    }

    /**
     * Normalise checkboxes and map wizard aliases → DB column names.
     */
    protected function prepareForValidation(): void
    {
        $payload = [
            'enable_exam_timer'        => (bool) $this->input('enable_exam_timer', false),
            'auto_submit_on_timer_end' => (bool) $this->input('auto_submit_on_timer_end', false),
            'shuffle_questions'        => (bool) $this->input('shuffle_questions', false),
            'shuffle_options'          => (bool) $this->input('shuffle_options', false),
            'enable_negative_marking'  => (bool) $this->input('enable_negative_marking', false),
            'fix_marks_each_question'  => (bool) $this->input('fix_marks_each_question', false),
            'fix_category_questions'   => (bool) $this->input('fix_category_questions', false),
            'fix_category_marks'       => (bool) $this->input('fix_category_marks', false),
            'ai_generated'             => (bool) $this->input('ai_generated', false),
            'ai_improve'               => (bool) $this->input('ai_improve', false),
            'attempt_limit_type'       => $this->input('attempt_limit_type') === 'fixed_count'
                ? 'fixed'
                : $this->input('attempt_limit_type'),
            'category_id'              => $this->input('exam_category_id') ?: $this->input('category_id'),
        ];

        foreach (['use_question_pool', 'fixed_questions', 'fixed_paper_set', 'shuffle_categories'] as $field) {
            if ($this->exists($field)) {
                $payload[$field] = $this->boolean($field);
            }
        }

        // Wizard aliases → DB columns (edit form already posts DB names).
        if (! $this->filled('duration') && $this->exists('exam_duration_minutes')) {
            $payload['duration'] = $this->input('exam_duration_minutes');
        }
        if (! $this->exists('scheduled_start') && $this->exists('schedule_start_at')) {
            $payload['scheduled_start'] = $this->input('schedule_start_at') ?: null;
        }
        if (! $this->exists('scheduled_end') && $this->exists('schedule_end_at')) {
            $payload['scheduled_end'] = $this->input('schedule_end_at') ?: null;
        }
        if (! $this->exists('max_attempts') && $this->exists('attempt_limit_count')) {
            $payload['max_attempts'] = $this->input('attempt_limit_count', 1);
        }

        if ($this->exists('exam_format')) {
            $examFormat = $this->decodeJsonValue($this->input('exam_format'));
            if (is_string($examFormat) && filled($examFormat)) {
                $examFormat = [$examFormat];
            }
            $payload['exam_format'] = is_array($examFormat) ? $examFormat : [];
        }

        $jsonListFields = [
            'predefined_instruction_rules',
            'tags',
            'selected_categories',
            'question_marks_filter',
            'manual_candidate_emails',
            'free_manual_candidate_emails',
            'extra_questions_categories',
        ];

        foreach ($jsonListFields as $field) {
            if ($this->exists($field)) {
                $payload[$field] = $this->normalizeJsonList($this->input($field, []));
            }
        }

        if ($this->exists('question_ids')) {
            $payload['question_ids'] = array_values(array_filter(array_map(
                'intval',
                $this->normalizeJsonList($this->input('question_ids', []))
            )));
        }

        $jsonObjectFields = [
            'imported_candidates',
            'free_imported_candidates',
            'extra_questions_allocations',
            'extra_marks_allocations',
            'selected_discounts',
            'custom_discounts',
            'category_question_rules',
        ];

        foreach ($jsonObjectFields as $field) {
            if ($this->exists($field)) {
                $decoded = $this->decodeJsonValue($this->input($field, []));
                if ($field === 'category_question_rules') {
                    $payload[$field] = $this->normalizeCategoryQuestionRules($decoded);
                } else {
                    $payload[$field] = $decoded;
                }
            }
        }

        $this->merge($payload);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $usePool = $this->boolean('use_question_pool');
            $fixedQuestions = $this->boolean('fixed_questions');
            $totalQuestions = (int) $this->input('total_questions', 0);
            $maximumQuestions = (int) $this->input('maximum_questions', 0);
            $questionIds = array_values(array_unique(array_map('intval', $this->input('question_ids', []))));
            $selectedCount = count($questionIds);

            if ($usePool && $this->exists('question_ids')) {
                if ($selectedCount < $totalQuestions || $selectedCount > $maximumQuestions) {
                    $validator->errors()->add(
                        'question_ids',
                        "Select between {$totalQuestions} and {$maximumQuestions} questions for the question pool."
                    );
                }
            } elseif ($fixedQuestions && $this->exists('question_ids')) {
                if ($selectedCount !== $totalQuestions) {
                    $validator->errors()->add(
                        'question_ids',
                        "Select exactly {$totalQuestions} question(s) when Fixed Questions is enabled."
                    );
                }
            } elseif (
                $this->exists('question_ids')
                && ! $usePool
                && ! $fixedQuestions
                && $selectedCount > 0
            ) {
                $validator->errors()->add(
                    'question_ids',
                    'Do not select specific questions when Fixed Questions and Question Pool are both disabled. Questions are assigned dynamically per candidate.'
                );
            }

            if ($this->boolean('fix_category_marks')) {
                $categories = $this->input('selected_categories', []);
                $categoryCount = is_array($categories) ? count($categories) : 0;
                $totalMarks = (int) $this->input('total_marks', 0);
                if ($categoryCount > 0 && $totalMarks > 0) {
                    $minimum = intdiv($totalMarks, $categoryCount);
                    $allocations = $this->input('extra_marks_allocations', []);
                    $allocated = is_array($allocations)
                        ? collect($allocations)->sum(fn ($marks) => max(0, (int) $marks))
                        : 0;
                    $belowMinimum = is_array($allocations)
                        && collect($allocations)->contains(fn ($marks) => (int) $marks < $minimum);

                    if ($allocated !== $totalMarks || $belowMinimum) {
                        $validator->errors()->add(
                            'extra_marks_allocations',
                            "Allocate exactly {$totalMarks} marks across categories (minimum {$minimum} each)."
                        );
                    }
                }
            }

            if ($this->boolean('fix_category_questions')) {
                $categories = $this->input('selected_categories', []);
                $categoryCount = is_array($categories) ? count($categories) : 0;
                if ($categoryCount > 0 && $totalQuestions > 0) {
                    $minimum = intdiv($totalQuestions, $categoryCount);
                    $allocations = $this->input('extra_questions_allocations', []);
                    $allocated = is_array($allocations)
                        ? collect($allocations)->sum(fn ($count) => max(0, (int) $count))
                        : 0;
                    $belowMinimum = is_array($allocations)
                        && collect($allocations)->contains(fn ($count) => (int) $count < $minimum);

                    if ($allocated !== $totalQuestions || $belowMinimum) {
                        $validator->errors()->add(
                            'extra_questions_allocations',
                            "Allocate exactly {$totalQuestions} questions across categories (minimum {$minimum} each)."
                        );
                    }
                }
            }
        });
    }

    protected function normalizeCategoryQuestionRules(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $rule) {
            if (! is_array($rule)) {
                continue;
            }
            $categoryId = (int) ($rule['category_id'] ?? $rule['categoryId'] ?? 0);
            $marks = (int) ($rule['marks'] ?? 0);
            $required = (int) ($rule['required'] ?? 0);
            if ($categoryId < 1 || $marks < 1 || $required < 1) {
                continue;
            }
            $normalized[] = [
                'category_id' => $categoryId,
                'marks' => $marks,
                'required' => $required,
            ];
        }

        return array_values($normalized);
    }

    protected function normalizeJsonList(mixed $value): array
    {
        $decoded = $this->decodeJsonValue($value);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static function ($item) {
                if (is_string($item) || is_numeric($item)) {
                    return trim((string) $item);
                }

                return $item;
            },
            $decoded
        ), static fn ($item) => $item !== '' && $item !== null));
    }

    protected function decodeJsonValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }
}
