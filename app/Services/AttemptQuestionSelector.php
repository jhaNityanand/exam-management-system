<?php

namespace App\Services;

use App\Exceptions\AttemptQuestionShortageException;
use App\Models\Exam;
use App\Models\ExamPart;
use App\Models\Question;
use Illuminate\Support\Collection;

class AttemptQuestionSelector
{
    public function __construct(protected QuestionBankService $questionBank) {}

    public function resolveMode(ExamPart $part): string
    {
        if ($part->use_question_pool) {
            return 'pool';
        }
        if ($part->fixed_questions) {
            return 'fixed';
        }

        return 'dynamic';
    }

    /**
     * Select questions for every part and return a flat ordered list.
     *
     * @return list<Question>
     *
     * @throws AttemptQuestionShortageException
     */
    public function select(Exam $exam): array
    {
        $exam->loadMissing('parts.questions', 'parts.selectedQuestionCategories');
        $parts = $exam->parts->sortBy('sort_order')->values();

        if ($parts->isEmpty()) {
            throw new AttemptQuestionShortageException(
                'Exam has no parts configured.',
                [['type' => 'parts_empty']]
            );
        }

        $selected = [];
        foreach ($parts as $part) {
            $selected = array_merge($selected, $this->selectForPart($exam, $part));
        }

        return $selected;
    }

    /**
     * @return list<Question>
     *
     * @throws AttemptQuestionShortageException
     */
    public function selectForPart(Exam $exam, ExamPart $part): array
    {
        return match ($this->resolveMode($part)) {
            'fixed' => $this->selectFixed($part),
            'pool' => $this->selectPool($part),
            default => $this->selectDynamic($exam, $part),
        };
    }

    /**
     * @return list<Question>
     */
    protected function selectFixed(ExamPart $part): array
    {
        $questions = $part->questions()
            ->wherePivot('status', 'active')
            ->orderByPivot('sort_order')
            ->get();

        $required = max(1, (int) $part->total_questions);
        if ($questions->count() < $required) {
            throw new AttemptQuestionShortageException(
                'Fixed part is missing required questions.',
                [[
                    'type' => 'fixed',
                    'part_id' => $part->id,
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
    protected function selectPool(ExamPart $part): array
    {
        $pool = $part->questions()
            ->wherePivot('status', 'active')
            ->orderByPivot('sort_order')
            ->get();

        $required = max(1, (int) $part->total_questions);
        if ($pool->count() < $required) {
            throw new AttemptQuestionShortageException(
                'Question pool is smaller than total questions.',
                [[
                    'type' => 'pool',
                    'part_id' => $part->id,
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
    protected function selectDynamic(Exam $exam, ExamPart $part): array
    {
        $filters = $this->baseFilters($exam, $part);
        $candidates = $this->questionBank
            ->filteredQuery((int) $exam->organization_id, $filters)
            ->with('category:id,name,parent_id')
            ->get();

        $required = max(1, (int) $part->total_questions);

        if ($part->fix_category_questions) {
            return $this->selectByCategoryCounts($exam, $part, $candidates, $required);
        }

        if ($part->fix_category_marks) {
            return $this->selectByCategoryMarks($exam, $part, $candidates, $required);
        }

        if ($candidates->count() < $required) {
            throw new AttemptQuestionShortageException(
                'Not enough matching questions for dynamic assignment.',
                [[
                    'type' => 'dynamic',
                    'part_id' => $part->id,
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
    protected function selectByCategoryCounts(Exam $exam, ExamPart $part, Collection $candidates, int $required): array
    {
        $allocations = $this->normalizeAllocations($part->extra_questions_allocations ?? []);
        $categoryIds = $this->selectedCategoryIds($part);
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
                    'part_id' => $part->id,
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
                    'part_id' => $part->id,
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
    protected function selectByCategoryMarks(Exam $exam, ExamPart $part, Collection $candidates, int $required): array
    {
        $allocations = $this->normalizeAllocations($part->extra_marks_allocations ?? []);
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
                    'part_id' => $part->id,
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
                [['type' => 'category_marks_empty', 'part_id' => $part->id]]
            );
        }

        if ($picked->count() > $required) {
            throw new AttemptQuestionShortageException(
                'Fixed category marks selection exceeds total questions.',
                [[
                    'type' => 'category_marks_count',
                    'part_id' => $part->id,
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
                        'part_id' => $part->id,
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
    protected function baseFilters(Exam $exam, ExamPart $part): array
    {
        $filters = [
            'categories' => $this->selectedCategoryIds($part),
            'marks' => array_values(array_filter(array_map('intval', $part->question_marks_filter ?? []))),
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
    protected function selectedCategoryIds(ExamPart $part): array
    {
        $ids = array_values(array_filter(array_map('intval', $part->selected_categories ?? [])));
        if ($ids !== []) {
            return $ids;
        }

        return $part->selectedQuestionCategories()
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
