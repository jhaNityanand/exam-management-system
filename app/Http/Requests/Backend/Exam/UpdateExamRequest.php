<?php

namespace App\Http\Requests\Backend\Exam;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateExamRequest
 *
 * Validates fields from the exam edit form (DB column names) while still
 * accepting create-wizard aliases for shared tooling. Question configuration
 * is validated per part when a parts payload is submitted.
 */
class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \App\Support\AdminCapabilities::userCan($this->user(), \App\Support\AdminCapabilities::CONTENT);
    }

    public function rules(): array
    {
        $categoryExists = Rule::exists('question_categories', 'id')->where(function ($query) {
            $orgId = current_organization_id();
            $query->where('status', 'active');
            if ($orgId) {
                $query->where('organization_id', $orgId);
            }
        });

        $questionExists = Rule::exists('questions', 'id')->where(function ($query) {
            $orgId = current_organization_id();
            $query->where('status', 'active');
            if ($orgId) {
                $query->where('organization_id', $orgId);
            }
        });

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
            'duration'                 => ['nullable', 'integer', 'min:1', 'max:999'],
            'exam_duration_minutes'    => ['nullable', 'integer', 'min:1', 'max:999'],
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

            // ── Section 6: Exam-level scoring rules ───────────────────────
            'passing_marks'                => ['sometimes', 'integer', 'min:0'],
            'enable_negative_marking'      => ['sometimes', 'boolean'],
            'negative_marking_type'        => [
                'nullable',
                Rule::in(['25', '33.33', '50', '100']),
            ],
            'negative_mark_per_question'   => ['nullable', 'numeric', 'min:0'],

            // ── Section 7: Parts (optional on update) ─────────────────────
            'parts'   => ['sometimes', 'array', 'min:1'],
            'parts.*.id' => ['nullable', 'integer'],
            'parts.*.name' => ['required_with:parts', 'string', 'max:255'],
            'parts.*.is_default' => ['sometimes', 'boolean'],
            'parts.*.total_questions' => ['required_with:parts', 'integer', 'min:1'],
            'parts.*.total_marks' => ['required_with:parts', 'integer', 'min:1'],
            'parts.*.use_question_pool' => ['sometimes', 'boolean'],
            'parts.*.maximum_questions' => ['nullable', 'integer', 'max:65535'],
            'parts.*.fixed_questions' => ['sometimes', 'boolean'],
            'parts.*.fixed_paper_set' => ['sometimes', 'boolean'],
            'parts.*.paper_sets' => ['nullable', 'integer', 'min:1'],
            'parts.*.fix_category_questions' => ['sometimes', 'boolean'],
            'parts.*.fix_category_marks' => ['sometimes', 'boolean'],
            'parts.*.distribution_type' => ['nullable', Rule::in(['mixed', 'category_wise', 'equal', 'weighted', 'manual'])],
            'parts.*.selected_categories' => ['required_with:parts', 'array', 'min:1'],
            'parts.*.selected_categories.*' => ['integer', $categoryExists],
            'parts.*.extra_questions_categories' => ['nullable'],
            'parts.*.extra_questions_allocations' => ['nullable'],
            'parts.*.extra_marks_allocations' => ['nullable', 'array'],
            'parts.*.extra_marks_allocations.*' => ['integer', 'min:0'],
            'parts.*.question_ids' => ['nullable', 'array'],
            'parts.*.question_ids.*' => ['integer', $questionExists],
            'parts.*.fix_marks_each_question' => ['sometimes', 'boolean'],
            'parts.*.question_marks_filter' => ['required_with:parts', 'array', 'min:1'],
            'parts.*.question_marks_filter.*' => ['integer', 'min:1', 'max:10'],
            'parts.*.shuffle_questions' => ['sometimes', 'boolean'],
            'parts.*.shuffle_categories' => ['sometimes', 'boolean'],
            'parts.*.shuffle_options' => ['sometimes', 'boolean'],
            'parts.*.category_question_rules' => ['nullable', 'array'],
            'parts.*.category_question_rules.*.category_id' => ['required_with:parts.*.category_question_rules', 'integer', $categoryExists],
            'parts.*.category_question_rules.*.marks' => ['required_with:parts.*.category_question_rules', 'integer', 'min:1', 'max:10'],
            'parts.*.category_question_rules.*.required' => ['required_with:parts.*.category_question_rules', 'integer', 'min:1'],

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
            'focus_violation_limit' => ['required', 'integer', 'min:0', 'max:99'],

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
            'parts.min'                     => 'Add at least one exam part.',
            'parts.*.name.required_with'    => 'Each part must have a name.',
            'parts.*.selected_categories.required_with' => 'Select at least one question category for each part.',
            'parts.*.question_marks_filter.required_with' => 'Select at least one question mark filter for each part.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = [
            'enable_exam_timer'        => (bool) $this->input('enable_exam_timer', false),
            'auto_submit_on_timer_end' => (bool) $this->input('auto_submit_on_timer_end', false),
            'enable_negative_marking'  => (bool) $this->input('enable_negative_marking', false),
            'ai_generated'             => (bool) $this->input('ai_generated', false),
            'ai_improve'               => (bool) $this->input('ai_improve', false),
            'attempt_limit_type'       => $this->input('attempt_limit_type') === 'fixed_count'
                ? 'fixed'
                : $this->input('attempt_limit_type'),
            'category_id'              => $this->input('exam_category_id') ?: $this->input('category_id'),
        ];

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

        if ($this->exists('focus_violation_limit')) {
            $payload['focus_violation_limit'] = $this->input('focus_violation_limit', 3);
        }

        $jsonListFields = [
            'predefined_instruction_rules',
            'tags',
            'manual_candidate_emails',
            'free_manual_candidate_emails',
        ];

        foreach ($jsonListFields as $field) {
            if ($this->exists($field)) {
                $payload[$field] = $this->normalizeJsonList($this->input($field, []));
            }
        }

        $jsonObjectFields = [
            'imported_candidates',
            'free_imported_candidates',
            'selected_discounts',
            'custom_discounts',
        ];

        foreach ($jsonObjectFields as $field) {
            if ($this->exists($field)) {
                $payload[$field] = $this->decodeJsonValue($this->input($field, []));
            }
        }

        if ($this->exists('parts') || $this->has('selected_categories') || $this->has('question_ids')) {
            $payload['parts'] = $this->normalizeParts($this->input('parts', []));
        }

        $this->merge($payload);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->exists('parts')) {
                return;
            }

            $parts = $this->input('parts', []);
            if (! is_array($parts)) {
                return;
            }

            if ($this->exists('passing_marks')) {
                $totalMarksSum = collect($parts)->sum(fn ($part) => (int) ($part['total_marks'] ?? 0));
                $passingMarks = (int) $this->input('passing_marks', 0);
                if ($totalMarksSum > 0 && $passingMarks > $totalMarksSum) {
                    $validator->errors()->add(
                        'passing_marks',
                        "Passing marks cannot exceed the total marks across all parts ({$totalMarksSum})."
                    );
                }
            }

            foreach ($parts as $index => $part) {
                if (! is_array($part)) {
                    continue;
                }
                $this->validatePart($validator, $part, (string) $index);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $part
     */
    protected function validatePart($validator, array $part, string $index): void
    {
        $prefix = "parts.{$index}";
        $usePool = (bool) ($part['use_question_pool'] ?? false);
        $fixedQuestions = (bool) ($part['fixed_questions'] ?? false);
        $totalQuestions = (int) ($part['total_questions'] ?? 0);
        $maximumQuestions = (int) ($part['maximum_questions'] ?? 0);
        $questionIds = array_values(array_unique(array_map(
            'intval',
            is_array($part['question_ids'] ?? null) ? $part['question_ids'] : []
        )));
        $selectedCount = count($questionIds);
        $hasQuestionIds = array_key_exists('question_ids', $part);

        if ($usePool) {
            if ($maximumQuestions <= $totalQuestions) {
                $validator->errors()->add(
                    "{$prefix}.maximum_questions",
                    'Maximum questions must be greater than total questions for the question pool.'
                );
            }

            if ($hasQuestionIds && ($selectedCount < $totalQuestions || $selectedCount > $maximumQuestions)) {
                $validator->errors()->add(
                    "{$prefix}.question_ids",
                    "Select between {$totalQuestions} and {$maximumQuestions} questions for the question pool."
                );
            }
        } elseif ($fixedQuestions) {
            if ($hasQuestionIds && $selectedCount !== $totalQuestions) {
                $validator->errors()->add(
                    "{$prefix}.question_ids",
                    "Select exactly {$totalQuestions} question(s) when Fixed Questions is enabled."
                );
            }
        } elseif ($hasQuestionIds && $selectedCount > 0) {
            $validator->errors()->add(
                "{$prefix}.question_ids",
                'Do not select specific questions when Fixed Questions and Question Pool are both disabled. Questions are assigned dynamically per candidate.'
            );
        }

        if ((bool) ($part['fixed_paper_set'] ?? false)) {
            $paperSets = (int) ($part['paper_sets'] ?? 0);
            if ($paperSets < 1) {
                $validator->errors()->add(
                    "{$prefix}.paper_sets",
                    'Enter the number of paper sets when Fixed Paper Set is enabled.'
                );
            } elseif ($totalQuestions > 0 && $paperSets > $totalQuestions) {
                $validator->errors()->add(
                    "{$prefix}.paper_sets",
                    'Paper sets cannot exceed the total number of questions.'
                );
            }
        }

        $rules = $part['category_question_rules'] ?? [];
        if (is_array($rules) && $rules !== []) {
            $ruleTotal = collect($rules)->sum(fn ($rule) => (int) ($rule['required'] ?? 0));
            $expected = $usePool ? $maximumQuestions : $totalQuestions;
            if (($part['fix_category_questions'] ?? false) && $expected > 0 && $ruleTotal !== $expected) {
                $validator->errors()->add(
                    "{$prefix}.category_question_rules",
                    "Category/mark required counts must total {$expected}."
                );
            }
        }

        if ((bool) ($part['fix_category_marks'] ?? false)) {
            $categories = $part['selected_categories'] ?? [];
            $categoryCount = is_array($categories) ? count($categories) : 0;
            $totalMarks = (int) ($part['total_marks'] ?? 0);
            if ($categoryCount > 0 && $totalMarks > 0) {
                $minimum = intdiv($totalMarks, $categoryCount);
                $allocations = $part['extra_marks_allocations'] ?? [];
                $allocated = is_array($allocations)
                    ? collect($allocations)->sum(fn ($marks) => max(0, (int) $marks))
                    : 0;
                $belowMinimum = is_array($allocations)
                    && collect($allocations)->contains(fn ($marks) => (int) $marks < $minimum);

                if ($allocated !== $totalMarks || $belowMinimum) {
                    $validator->errors()->add(
                        "{$prefix}.extra_marks_allocations",
                        "Allocate exactly {$totalMarks} marks across categories (minimum {$minimum} each)."
                    );
                }
            }
        }

        if ((bool) ($part['fix_category_questions'] ?? false)) {
            $categories = $part['selected_categories'] ?? [];
            $categoryCount = is_array($categories) ? count($categories) : 0;
            if ($categoryCount > 0 && $totalQuestions > 0) {
                $minimum = intdiv($totalQuestions, $categoryCount);
                $allocations = $part['extra_questions_allocations'] ?? [];
                $allocated = is_array($allocations)
                    ? collect($allocations)->sum(fn ($count) => max(0, (int) $count))
                    : 0;
                $belowMinimum = is_array($allocations)
                    && collect($allocations)->contains(fn ($count) => (int) $count < $minimum);

                if ($allocated !== $totalQuestions || $belowMinimum) {
                    $validator->errors()->add(
                        "{$prefix}.extra_questions_allocations",
                        "Allocate exactly {$totalQuestions} questions across categories (minimum {$minimum} each)."
                    );
                }
            }
        }
    }

    protected function normalizeParts(mixed $parts): array
    {
        $parts = $this->decodeJsonValue($parts);

        if ((! is_array($parts) || $parts === []) && ($this->has('total_questions') || $this->has('selected_categories') || $this->has('question_ids'))) {
            $parts = [
                [
                    'name' => 'Part 1',
                    'is_default' => true,
                    'total_questions' => (int) $this->input('total_questions', 10),
                    'total_marks' => (int) $this->input('total_marks', 10),
                    'use_question_pool' => $this->boolean('use_question_pool', false),
                    'maximum_questions' => $this->input('maximum_questions'),
                    'fixed_questions' => $this->boolean('fixed_questions', false),
                    'fixed_paper_set' => $this->boolean('fixed_paper_set', false),
                    'paper_sets' => $this->input('paper_sets'),
                    'fix_category_questions' => $this->boolean('fix_category_questions', false),
                    'fix_category_marks' => $this->boolean('fix_category_marks', false),
                    'distribution_type' => $this->input('distribution_type', 'mixed'),
                    'selected_categories' => $this->input('selected_categories', []),
                    'extra_questions_categories' => $this->input('extra_questions_categories'),
                    'extra_questions_allocations' => $this->input('extra_questions_allocations'),
                    'extra_marks_allocations' => $this->input('extra_marks_allocations'),
                    'question_ids' => $this->input('question_ids', []),
                    'fix_marks_each_question' => $this->boolean('fix_marks_each_question', false),
                    'question_marks_filter' => $this->input('question_marks_filter', [1]),
                    'shuffle_questions' => $this->boolean('shuffle_questions', false),
                    'shuffle_categories' => $this->boolean('shuffle_categories', false),
                    'shuffle_options' => $this->boolean('shuffle_options', false),
                    'category_question_rules' => $this->input('category_question_rules', []),
                ],
            ];
        }

        if (! is_array($parts)) {
            return [];
        }

        return array_values(array_map(
            fn ($part) => $this->normalizePart(is_array($part) ? $part : []),
            $parts
        ));
    }

    /**
     * @param  array<string, mixed>  $part
     * @return array<string, mixed>
     */
    protected function normalizePart(array $part): array
    {
        foreach ([
            'is_default',
            'use_question_pool',
            'fixed_questions',
            'fixed_paper_set',
            'fix_category_questions',
            'fix_category_marks',
            'fix_marks_each_question',
            'shuffle_questions',
            'shuffle_categories',
            'shuffle_options',
        ] as $field) {
            if (array_key_exists($field, $part)) {
                $part[$field] = (bool) $part[$field];
            }
        }

        $part['selected_categories'] = array_values(array_filter(array_map(
            'intval',
            $this->normalizeJsonList($part['selected_categories'] ?? [])
        ), static fn (int $id) => $id > 0));

        $part['question_marks_filter'] = array_values(array_filter(array_map(
            'intval',
            $this->normalizeJsonList($part['question_marks_filter'] ?? [])
        ), static fn (int $mark) => $mark > 0));

        if (array_key_exists('question_ids', $part)) {
            $part['question_ids'] = array_values(array_filter(array_map(
                'intval',
                $this->normalizeJsonList($part['question_ids'] ?? [])
            )));
        }

        if (array_key_exists('extra_questions_categories', $part)) {
            $part['extra_questions_categories'] = $this->normalizeJsonList($part['extra_questions_categories']);
        }

        if (array_key_exists('extra_questions_allocations', $part)) {
            $part['extra_questions_allocations'] = $this->decodeJsonValue($part['extra_questions_allocations']) ?? [];
        }

        if (array_key_exists('extra_marks_allocations', $part)) {
            $part['extra_marks_allocations'] = $this->decodeJsonValue($part['extra_marks_allocations']) ?? [];
        }

        if (array_key_exists('category_question_rules', $part)) {
            $part['category_question_rules'] = $this->normalizeCategoryQuestionRules(
                $this->decodeJsonValue($part['category_question_rules'])
            );
        }

        return $part;
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
