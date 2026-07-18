<?php

namespace App\Services;

use App\Exceptions\AttemptQuestionShortageException;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Support\Collection;

class AttemptQuestionSelector
{
    public function __construct(protected QuestionBankService $questionBank) {}

    public function resolveMode(Exam $exam): string
    {
        if ($exam->use_question_pool) {
            return 'pool';
        }
        if ($exam->fixed_questions) {
            return 'fixed';
        }

        return 'dynamic';
    }

    /**
     * @return list<Question>
     *
     * @throws AttemptQuestionShortageException
     */
    public function select(Exam $exam): array
    {
        return match ($this->resolveMode($exam)) {
            'fixed' => $this->selectFixed($exam),
            'pool' => $this->selectPool($exam),
            default => $this->selectDynamic($exam),
        };
    }

    /**
     * @return list<Question>
     */
    protected function selectFixed(Exam $exam): array
    {
        $questions = $exam->questions()
            ->wherePivot('status', 'active')
            ->orderByPivot('sort_order')
            ->get();

        $required = max(1, (int) $exam->total_questions);
        if ($questions->count() < $required) {
            throw new AttemptQuestionShortageException(
                'Fixed exam is missing required questions.',
                [[
                    'type' => 'fixed',
                    'required' => $required,
                    'available' => $questions->count(),
                    'missing' => $required - $questions->count(),
                ]]
            );
        }

        return $questions->take($required)->all();
    }

    /**
     * @return list<Question>
     */
    protected function selectPool(Exam $exam): array
    {
        $pool = $exam->questions()
            ->wherePivot('status', 'active')
            ->orderByPivot('sort_order')
            ->get();

        $required = max(1, (int) $exam->total_questions);
        if ($pool->count() < $required) {
            throw new AttemptQuestionShortageException(
                'Question pool is smaller than total questions.',
                [[
                    'type' => 'pool',
                    'required' => $required,
                    'available' => $pool->count(),
                    'missing' => $required - $pool->count(),
                ]]
            );
        }

        return $pool->shuffle()->take($required)->values()->all();
    }

    /**
     * @return list<Question>
     */
    protected function selectDynamic(Exam $exam): array
    {
        $filters = $this->baseFilters($exam);
        $candidates = $this->questionBank
            ->filteredQuery((int) $exam->organization_id, $filters)
            ->with('category:id,name,parent_id')
            ->get();

        $required = max(1, (int) $exam->total_questions);

        if ($exam->fix_category_questions) {
            return $this->selectByCategoryCounts($exam, $candidates, $required);
        }

        if ($exam->fix_category_marks) {
            return $this->selectByCategoryMarks($exam, $candidates, $required);
        }

        if ($candidates->count() < $required) {
            throw new AttemptQuestionShortageException(
                'Not enough matching questions for dynamic assignment.',
                [[
                    'type' => 'dynamic',
                    'required' => $required,
                    'available' => $candidates->count(),
                    'missing' => $required - $candidates->count(),
                ]]
            );
        }

        return $candidates->shuffle()->take($required)->values()->all();
    }

    /**
     * @param  Collection<int, Question>  $candidates
     * @return list<Question>
     */
    protected function selectByCategoryCounts(Exam $exam, Collection $candidates, int $required): array
    {
        $allocations = $this->normalizeAllocations($exam->extra_questions_allocations ?? []);
        $categoryIds = $this->selectedCategoryIds($exam);
        if ($allocations === [] && $categoryIds !== []) {
            $allocations = $this->evenSplit($required, $categoryIds);
        }

        $picked = collect();
        $report = [];

        foreach ($allocations as $categoryId => $count) {
            $count = max(0, (int) $count);
            if ($count === 0) {
                continue;
            }

            $scopeIds = $this->questionBank->getDescendantCategoryIds(
                (int) $exam->organization_id,
                [(int) $categoryId]
            );
            $pool = $candidates
                ->filter(fn (Question $q) => in_array((int) $q->category_id, $scopeIds, true))
                ->values();

            if ($pool->count() < $count) {
                $report[] = [
                    'type' => 'category_count',
                    'category_id' => (int) $categoryId,
                    'required' => $count,
                    'available' => $pool->count(),
                    'missing' => $count - $pool->count(),
                ];
                continue;
            }

            $picked = $picked->merge($pool->shuffle()->take($count));
        }

        if ($report !== []) {
            throw new AttemptQuestionShortageException(
                'Unable to satisfy fixed category question counts.',
                $report
            );
        }

        if ($picked->count() < $required) {
            throw new AttemptQuestionShortageException(
                'Category allocations do not reach total questions.',
                [[
                    'type' => 'category_count_total',
                    'required' => $required,
                    'available' => $picked->count(),
                    'missing' => $required - $picked->count(),
                ]]
            );
        }

        return $picked->unique('id')->take($required)->values()->all();
    }

    /**
     * @param  Collection<int, Question>  $candidates
     * @return list<Question>
     */
    protected function selectByCategoryMarks(Exam $exam, Collection $candidates, int $required): array
    {
        $allocations = $this->normalizeAllocations($exam->extra_marks_allocations ?? []);
        $picked = collect();
        $report = [];

        foreach ($allocations as $categoryId => $marksTarget) {
            $marksTarget = max(0, (int) $marksTarget);
            if ($marksTarget === 0) {
                continue;
            }

            $scopeIds = $this->questionBank->getDescendantCategoryIds(
                (int) $exam->organization_id,
                [(int) $categoryId]
            );
            $pool = $candidates
                ->filter(fn (Question $q) => in_array((int) $q->category_id, $scopeIds, true))
                ->values();

            $subset = $this->findExactMarksSubset($pool, $marksTarget);
            if ($subset === null) {
                $report[] = [
                    'type' => 'category_marks',
                    'category_id' => (int) $categoryId,
                    'required_marks' => $marksTarget,
                    'available' => $pool->count(),
                ];
                continue;
            }

            $picked = $picked->merge($subset);
        }

        if ($report !== []) {
            throw new AttemptQuestionShortageException(
                'Unable to satisfy fixed category marks allocations.',
                $report
            );
        }

        if ($picked->count() < 1) {
            throw new AttemptQuestionShortageException(
                'No questions selected for fixed category marks.',
                [['type' => 'category_marks_empty']]
            );
        }

        // Prefer exact marks selection; if it exceeds total_questions, fail.
        if ($picked->count() > $required) {
            throw new AttemptQuestionShortageException(
                'Fixed category marks selection exceeds total questions.',
                [[
                    'type' => 'category_marks_count',
                    'required' => $required,
                    'available' => $picked->count(),
                ]]
            );
        }

        if ($picked->count() < $required) {
            $remaining = $required - $picked->count();
            $leftover = $candidates
                ->reject(fn (Question $q) => $picked->contains('id', $q->id))
                ->shuffle()
                ->take($remaining);
            if ($leftover->count() < $remaining) {
                throw new AttemptQuestionShortageException(
                    'Not enough questions to fill remaining seats after marks allocation.',
                    [[
                        'type' => 'category_marks_fill',
                        'required' => $remaining,
                        'available' => $leftover->count(),
                    ]]
                );
            }
            $picked = $picked->merge($leftover);
        }

        return $picked->unique('id')->values()->all();
    }

    /**
     * @param  Collection<int, Question>  $pool
     * @return Collection<int, Question>|null
     */
    protected function findExactMarksSubset(Collection $pool, int $target): ?Collection
    {
        $items = $pool->shuffle()->values();
        $n = $items->count();
        if ($n === 0) {
            return null;
        }

        // DP subset-sum with reconstruction, bounded for practical bank sizes.
        $limit = min($n, 40);
        $items = $items->take($limit)->values();
        $dp = [0 => []];

        foreach ($items as $index => $question) {
            $mark = max(0, (int) $question->marks);
            if ($mark === 0) {
                continue;
            }
            foreach (array_reverse($dp, true) as $sum => $path) {
                $next = $sum + $mark;
                if ($next > $target || isset($dp[$next])) {
                    continue;
                }
                $dp[$next] = array_merge($path, [$index]);
                if ($next === $target) {
                    return collect($dp[$next])->map(fn (int $i) => $items[$i])->values();
                }
            }
        }

        return isset($dp[$target])
            ? collect($dp[$target])->map(fn (int $i) => $items[$i])->values()
            : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function baseFilters(Exam $exam): array
    {
        $filters = [
            'categories' => $this->selectedCategoryIds($exam),
            'marks' => array_values(array_filter(array_map('intval', $exam->question_marks_filter ?? []))),
            'formats' => array_values(array_filter(array_map('strval', $exam->exam_format ?? []))),
        ];

        if (! empty($exam->difficulty_level) && $exam->difficulty_level !== 'mixed') {
            $filters['difficulty'] = [$exam->difficulty_level];
        }

        return $filters;
    }

    /**
     * @return list<int>
     */
    protected function selectedCategoryIds(Exam $exam): array
    {
        $ids = array_values(array_filter(array_map('intval', $exam->selected_categories ?? [])));
        if ($ids !== []) {
            return $ids;
        }

        return $exam->selectedQuestionCategories()
            ->pluck('question_categories.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  mixed  $allocations
     * @return array<int, int>
     */
    protected function normalizeAllocations(mixed $allocations): array
    {
        if (! is_array($allocations)) {
            return [];
        }

        $normalized = [];
        foreach ($allocations as $key => $value) {
            $id = (int) $key;
            if ($id <= 0) {
                continue;
            }
            $normalized[$id] = (int) $value;
        }

        return $normalized;
    }

    /**
     * @param  list<int>  $categoryIds
     * @return array<int, int>
     */
    protected function evenSplit(int $total, array $categoryIds): array
    {
        $count = count($categoryIds);
        if ($count === 0) {
            return [];
        }
        $base = intdiv($total, $count);
        $remainder = $total % $count;
        $result = [];
        foreach (array_values($categoryIds) as $index => $categoryId) {
            $result[(int) $categoryId] = $base + ($index < $remainder ? 1 : 0);
        }

        return $result;
    }
}
