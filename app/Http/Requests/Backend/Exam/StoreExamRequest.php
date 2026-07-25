<?php

namespace App\Http\Requests\Backend\Exam;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreExamRequest
 *
 * Validates all fields submitted from the exam creation form.
 * Exam-level settings plus one or more parts (question config per part).
 */
class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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

            // ── Section 6: Exam-level scoring rules ───────────────────────
            'passing_marks'                => ['required', 'integer', 'min:0'],
            'enable_negative_marking'      => ['sometimes', 'boolean'],
            'negative_marking_type'        => [
                Rule::requiredIf(fn () => $this->boolean('enable_negative_marking')),
                'nullable',
                Rule::in(['25', '33.33', '50', '100']),
            ],
            'negative_mark_per_question'   => ['nullable', 'numeric', 'min:0'],

            // ── Section 7: Parts ──────────────────────────────────────────
            'parts'   => ['required', 'array', 'min:1'],
            'parts.*.id' => ['nullable', 'integer'],
            'parts.*.name' => ['required', 'string', 'max:255'],
            'parts.*.is_default' => ['sometimes', 'boolean'],
            'parts.*.total_questions' => ['required', 'integer', 'min:1'],
            'parts.*.total_marks' => ['required', 'integer', 'min:1'],
            'parts.*.use_question_pool' => ['sometimes', 'boolean'],
            'parts.*.maximum_questions' => ['nullable', 'integer', 'max:65535'],
            'parts.*.fixed_questions' => ['sometimes', 'boolean'],
            'parts.*.fixed_paper_set' => ['sometimes', 'boolean'],
            'parts.*.paper_sets' => ['nullable', 'integer', 'min:1'],
            'parts.*.fix_category_questions' => ['sometimes', 'boolean'],
            'parts.*.fix_category_marks' => ['sometimes', 'boolean'],
            'parts.*.distribution_type' => ['nullable', Rule::in(['mixed', 'category_wise', 'equal', 'weighted', 'manual'])],
            'parts.*.selected_categories' => ['required', 'array', 'min:1'],
            'parts.*.selected_categories.*' => ['integer', $categoryExists],
            'parts.*.extra_questions_categories' => ['nullable'],
            'parts.*.extra_questions_allocations' => ['nullable'],
            'parts.*.extra_marks_allocations' => ['nullable', 'array'],
            'parts.*.extra_marks_allocations.*' => ['integer', 'min:0'],
            'parts.*.question_ids' => ['nullable', 'array'],
            'parts.*.question_ids.*' => ['integer', $questionExists],
            'parts.*.fix_marks_each_question' => ['sometimes', 'boolean'],
            'parts.*.question_marks_filter' => ['required', 'array', 'min:1'],
            'parts.*.question_marks_filter.*' => ['integer', 'min:1', 'max:10'],
            'parts.*.shuffle_questions' => ['sometimes', 'boolean'],
            'parts.*.shuffle_categories' => ['sometimes', 'boolean'],
            'parts.*.shuffle_options' => ['sometimes', 'boolean'],
            'parts.*.category_question_rules' => ['nullable', 'array'],
            'parts.*.category_question_rules.*.category_id' => ['required_with:parts.*.category_question_rules', 'integer'],
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
            'title.required'                  => 'Please enter an exam title.',
            'status.required'                 => 'Please select a status.',
            'exam_mode.required'              => 'Please select an exam mode.',
            'visibility.required'             => 'Please select a visibility option.',
            'exam_format.required'            => 'Please select at least one exam format.',
            'schedule_type.required'          => 'Please select a schedule type.',
            'schedule_start_at.required_if'   => 'Please set a start date for the fixed window.',
            'schedule_end_at.required_if'     => 'Please set an end date for the fixed window.',
            'schedule_end_at.after'           => 'End date must be after the start date.',
            'attempt_limit_type.required'     => 'Please select an attempt limit type.',
            'attempt_limit_count.required_if' => 'Please enter the maximum number of attempts.',
            'passing_marks.required'          => 'Passing marks is required.',
            'parts.required'                  => 'Add at least one exam part.',
            'parts.min'                       => 'Add at least one exam part.',
            'parts.*.name.required'           => 'Each part must have a name.',
            'parts.*.total_questions.required'=> 'Total questions is required for each part.',
            'exam_duration_minutes.required'  => 'Exam duration is required.',
            'parts.*.total_marks.required'    => 'Total marks is required for each part.',
            'parts.*.selected_categories.required' => 'Select at least one question category for each part.',
            'parts.*.question_marks_filter.required' => 'Select at least one question mark filter for each part.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $examFormat = $this->decodeJsonValue($this->input('exam_format'));
        if (is_string($examFormat) && filled($examFormat)) {
            $examFormat = [$examFormat];
        }

        $this->merge([
            'scheduled_start'          => $this->input('schedule_start_at') ?: null,
            'scheduled_end'            => $this->input('schedule_end_at') ?: null,
            'max_attempts'             => $this->input('attempt_limit_count', 1),

            'enable_exam_timer'        => (bool) $this->input('enable_exam_timer', false),
            'auto_submit_on_timer_end' => (bool) $this->input('auto_submit_on_timer_end', false),
            'enable_negative_marking'  => (bool) $this->input('enable_negative_marking', false),
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
            'focus_violation_limit' => $this->input('focus_violation_limit', 3),
            'tags' => $this->normalizeJsonList($this->input('tags')),
            'imported_candidates' => $this->decodeJsonValue($this->input('imported_candidates', [])),
            'manual_candidate_emails' => $this->normalizeJsonList($this->input('manual_candidate_emails')),
            'free_imported_candidates' => $this->decodeJsonValue($this->input('free_imported_candidates', [])),
            'free_manual_candidate_emails' => $this->normalizeJsonList($this->input('free_manual_candidate_emails')),
            'selected_discounts' => $this->decodeJsonValue($this->input('selected_discounts', [])),
            'custom_discounts' => $this->decodeJsonValue($this->input('custom_discounts', [])),
            'parts' => $this->normalizeParts($this->input('parts', [])),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $parts = $this->input('parts', []);
            if (! is_array($parts)) {
                return;
            }

            $totalMarksSum = collect($parts)->sum(fn ($part) => (int) ($part['total_marks'] ?? 0));
            $passingMarks = (int) $this->input('passing_marks', 0);
            if ($totalMarksSum > 0 && $passingMarks > $totalMarksSum) {
                $validator->errors()->add(
                    'passing_marks',
                    "Passing marks cannot exceed the total marks across all parts ({$totalMarksSum})."
                );
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

        if ($usePool) {
            if ($maximumQuestions <= $totalQuestions) {
                $validator->errors()->add(
                    "{$prefix}.maximum_questions",
                    'Maximum questions must be greater than total questions for the question pool.'
                );
            }

            if ($selectedCount < $totalQuestions || $selectedCount > $maximumQuestions) {
                $validator->errors()->add(
                    "{$prefix}.question_ids",
                    "Select between {$totalQuestions} and {$maximumQuestions} questions for the question pool."
                );
            }
        } elseif ($fixedQuestions) {
            if ($selectedCount !== $totalQuestions) {
                $validator->errors()->add(
                    "{$prefix}.question_ids",
                    "Select exactly {$totalQuestions} question(s) when Fixed Questions is enabled."
                );
            }
        } elseif ($selectedCount > 0) {
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

        $part['question_ids'] = array_values(array_filter(array_map(
            'intval',
            $this->normalizeJsonList($part['question_ids'] ?? [])
        )));

        if (array_key_exists('extra_questions_categories', $part)) {
            $part['extra_questions_categories'] = $this->normalizeJsonList($part['extra_questions_categories']);
        }

        if (array_key_exists('extra_questions_allocations', $part)) {
            $part['extra_questions_allocations'] = $this->decodeJsonValue($part['extra_questions_allocations']) ?? [];
        }

        if (array_key_exists('extra_marks_allocations', $part)) {
            $part['extra_marks_allocations'] = $this->decodeJsonValue($part['extra_marks_allocations']) ?? [];
        }

        $part['category_question_rules'] = $this->normalizeCategoryQuestionRules(
            $this->decodeJsonValue($part['category_question_rules'] ?? [])
        );

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
