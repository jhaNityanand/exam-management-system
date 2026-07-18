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
            'status'           => ['required', Rule::in(['draft', 'published', 'active', 'inactive', 'suspended'])],
            'exam_mode'        => ['required', Rule::in(['standard', 'practice', 'proctored'])],
            'visibility'       => ['required', Rule::in(['public', 'private', 'invite_only'])],
            'tags'             => ['nullable'],

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
            'imported_candidates'          => ['nullable'],
            'manual_candidate_emails'      => ['nullable'],
            'free_imported_candidates'     => ['nullable'],
            'free_manual_candidate_emails' => ['nullable'],

            // ── Section 6: Exam Configuration ─────────────────────────────
            'total_questions'              => ['required', 'integer', 'min:1'],
            'total_marks'                  => ['required', 'integer', 'min:1'],
            'passing_marks'                => ['required', 'integer', 'min:0', 'lte:total_marks'],
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
            'paper_sets'                   => [Rule::requiredIf(fn () => $this->boolean('fixed_paper_set')), 'nullable', 'integer', 'min:1', 'lte:total_questions'],
            'fix_category_questions'       => ['sometimes', 'boolean'],
            'fix_category_marks'           => ['sometimes', 'boolean'],
            'distribution_type'            => ['nullable', Rule::in(['mixed', 'category_wise', 'equal', 'weighted', 'manual'])],
            'selected_categories'          => ['required', 'array', 'min:1'],
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
                Rule::requiredIf(fn () => $this->boolean('enable_negative_marking')),
                'nullable',
                Rule::in(['25', '33.33', '50', '100']),
            ],
            'negative_mark_per_question' => ['nullable', 'numeric', 'min:0'],
            'question_marks_filter'      => ['required', 'array', 'min:1'],
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
            'schedule_start_at.required_if' => 'Please set a start date for the fixed window.',
            'schedule_end_at.required_if'   => 'Please set an end date for the fixed window.',
            'schedule_end_at.after'         => 'End date must be after the start date.',
            'attempt_limit_type.required'   => 'Please select an attempt limit type.',
            'attempt_limit_count.required_if' => 'Please enter the maximum number of attempts.',
            'total_questions.required'      => 'Total questions is required.',
            'maximum_questions.required'    => 'Enter the maximum question-pool size.',
            'maximum_questions.gt'          => 'Maximum questions must be greater than Total Questions Ask.',
            'total_marks.required'          => 'Total marks is required.',
            'passing_marks.required'        => 'Passing marks is required.',
            'paper_sets.required'           => 'Enter the number of paper sets when Fixed Paper Set is enabled.',
            'paper_sets.lte'                => 'Paper sets cannot exceed the total number of questions.',
            'exam_duration_minutes.required'=> 'Please enter the exam duration.',
        ];
    }

    /**
     * Prepare the data before validation — normalise checkboxes, map field names.
     */
    protected function prepareForValidation(): void
    {
        $examFormat = $this->decodeJsonValue($this->input('exam_format'));
        if (is_string($examFormat) && filled($examFormat)) {
            $examFormat = [$examFormat];
        }

        $this->merge([
            'duration'                 => $this->input('exam_duration_minutes'),
            'scheduled_start'          => $this->input('schedule_start_at') ?: null,
            'scheduled_end'            => $this->input('schedule_end_at') ?: null,
            'max_attempts'             => $this->input('attempt_limit_count', 1),

            'enable_exam_timer'        => (bool) $this->input('enable_exam_timer', false),
            'auto_submit_on_timer_end' => (bool) $this->input('auto_submit_on_timer_end', false),
            'use_question_pool'         => (bool) $this->input('use_question_pool', false),
            'fixed_questions'          => (bool) $this->input('fixed_questions', false),
            'fixed_paper_set'          => (bool) $this->input('fixed_paper_set', false),
            'shuffle_questions'        => (bool) $this->input('shuffle_questions', false),
            'shuffle_categories'       => (bool) $this->input('shuffle_categories', false),
            'shuffle_options'          => (bool) $this->input('shuffle_options', false),
            'enable_negative_marking'  => (bool) $this->input('enable_negative_marking', false),
            'fix_marks_each_question'  => (bool) $this->input('fix_marks_each_question', false),
            'fix_category_questions'   => (bool) $this->input('fix_category_questions', false),
            'fix_category_marks'       => (bool) $this->input('fix_category_marks', false),
            'ai_generated'             => (bool) $this->input('ai_generated', false),
            'ai_improve'               => (bool) $this->input('ai_improve', false),

            'category_id'              => $this->input('exam_category_id') ?: $this->input('category_id'),
            'exam_format'              => is_array($examFormat) ? $examFormat : [],
            'attempt_limit_type'       => $this->input('attempt_limit_type') === 'fixed_count'
                ? 'fixed'
                : $this->input('attempt_limit_type'),
            'predefined_instruction_rules' => $this->normalizeJsonList(
                $this->input('predefined_instruction_rules')
            ),
            'tags' => $this->normalizeJsonList($this->input('tags')),
            'selected_categories' => array_values(array_filter(array_map(
                'intval',
                $this->normalizeJsonList($this->input('selected_categories'))
            ), static fn (int $id) => $id > 0)),
            'question_marks_filter' => array_values(array_filter(array_map(
                'intval',
                $this->normalizeJsonList($this->input('question_marks_filter'))
            ), static fn (int $mark) => $mark > 0)),
            'question_ids' => array_values(array_filter(array_map(
                'intval',
                $this->normalizeJsonList($this->input('question_ids'))
            ))),
            'imported_candidates' => $this->decodeJsonValue($this->input('imported_candidates', [])),
            'manual_candidate_emails' => $this->normalizeJsonList($this->input('manual_candidate_emails')),
            'free_imported_candidates' => $this->decodeJsonValue($this->input('free_imported_candidates', [])),
            'free_manual_candidate_emails' => $this->normalizeJsonList($this->input('free_manual_candidate_emails')),
            'extra_questions_categories' => $this->normalizeJsonList($this->input('extra_questions_categories')),
            'extra_questions_allocations' => $this->decodeJsonValue($this->input('extra_questions_allocations', [])),
            'extra_marks_allocations' => $this->decodeJsonValue($this->input('extra_marks_allocations', [])),
            'selected_discounts' => $this->decodeJsonValue($this->input('selected_discounts', [])),
            'custom_discounts' => $this->decodeJsonValue($this->input('custom_discounts', [])),
            'category_question_rules' => $this->normalizeCategoryQuestionRules(
                $this->decodeJsonValue($this->input('category_question_rules', []))
            ),
        ]);
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

            if ($usePool) {
                if ($selectedCount < $totalQuestions || $selectedCount > $maximumQuestions) {
                    $validator->errors()->add(
                        'question_ids',
                        "Select between {$totalQuestions} and {$maximumQuestions} questions for the question pool."
                    );
                }
            } elseif ($fixedQuestions) {
                if ($selectedCount !== $totalQuestions) {
                    $validator->errors()->add(
                        'question_ids',
                        "Select exactly {$totalQuestions} question(s) when Fixed Questions is enabled."
                    );
                }
            } elseif ($selectedCount > 0) {
                $validator->errors()->add(
                    'question_ids',
                    'Do not select specific questions when Fixed Questions and Question Pool are both disabled. Questions are assigned dynamically per candidate.'
                );
            }

            $rules = $this->input('category_question_rules', []);
            if (is_array($rules) && $rules !== []) {
                $ruleTotal = collect($rules)->sum(fn ($rule) => (int) ($rule['required'] ?? 0));
                $expected = $usePool ? $maximumQuestions : $totalQuestions;
                if ($this->boolean('fix_category_questions') && $expected > 0 && $ruleTotal !== $expected) {
                    $validator->errors()->add(
                        'category_question_rules',
                        "Category/mark required counts must total {$expected}."
                    );
                }
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
                $totalQuestions = (int) $this->input('total_questions', 0);
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
