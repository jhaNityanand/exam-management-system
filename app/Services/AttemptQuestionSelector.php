<?php

namespace App\Services;

use App\Exceptions\AttemptQuestionShortageException;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamPart;
use App\Models\Question;
use App\Models\User;
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
    public function select(Exam $exam, ?User $user = null): array
    {
        $exam->loadMissing('parts.questions', 'parts.selectedQuestionCategories');
        $parts = $exam->parts->sortBy('sort_order')->values();

        if ($parts->isEmpty()) {
            throw new AttemptQuestionShortageException(
                'Exam has no parts configured.',
                [['type' => 'parts_empty']]
            );
        }

        $retakeHistory = $this->getRetakeHistory($exam, $user);
        $selected = [];
        foreach ($parts as $part) {
            $selected = array_merge($selected, $this->selectForPart($exam, $part, $user, $retakeHistory));
        }

        return $selected;
    }

    /**
     * @return list<Question>
     *
     * @throws AttemptQuestionShortageException
     */
    public function selectForPart(Exam $exam, ExamPart $part, ?User $user = null, ?array $retakeHistory = null): array
    {
        $retakeHistory ??= $this->getRetakeHistory($exam, $user);

        return match ($this->resolveMode($part)) {
            'fixed' => $this->selectFixed($part, $retakeHistory),
            'pool' => $this->selectPool($part, $retakeHistory),
            default => $this->selectDynamic($exam, $part, $retakeHistory),
        };
    }

    /**
     * @return list<Question>
     */
    protected function selectFixed(ExamPart $part, array $retakeHistory = []): array
    {
        $questions = $part->questions()
            ->wherePivot('status', 'active')
            ->orderByPivot('sort_order')
            ->get();

        $required = max(1, (int) $part->total_questions);
        if ($questions->isEmpty()) {
            throw new AttemptQuestionShortageException(
                'Fixed part has no active questions attached.',
                [[
                    'type' => 'fixed',
                    'part_id' => $part->id,
                    'required' => $required,
                    'available' => 0,
                    'missing' => $required,
                ]]
            );
        }

        $prioritized = $this->prioritizeCandidates($questions, $retakeHistory, false);

        if ($prioritized->count() < $required) {
            $prioritized = $this->fillRemainingWithRepetition($prioritized, $required, 'fixed_repetition');
        }

        return $prioritized->take($required)->values()->all();
    }

    /**
     * @return list<Question>
     */
    protected function selectPool(ExamPart $part, array $retakeHistory = []): array
    {
        $pool = $part->questions()
            ->wherePivot('status', 'active')
            ->orderByPivot('sort_order')
            ->get();

        $required = max(1, (int) $part->total_questions);
        if ($pool->isEmpty()) {
            throw new AttemptQuestionShortageException(
                'Question pool is empty.',
                [[
                    'type' => 'pool',
                    'part_id' => $part->id,
                    'required' => $required,
                    'available' => 0,
                    'missing' => $required,
                ]]
            );
        }

        $prioritized = $this->prioritizeCandidates($pool, $retakeHistory, true);

        if ($prioritized->count() < $required) {
            $prioritized = $this->fillRemainingWithRepetition($prioritized, $required, 'pool_repetition');
        }

        return $prioritized->take($required)->values()->all();
    }

    /**
     * @return list<Question>
     */
    protected function selectDynamic(Exam $exam, ExamPart $part, array $retakeHistory = []): array
    {
        $filters = $this->baseFilters($exam, $part);
        $candidates = $this->questionBank
            ->filteredQuery((int) $exam->organization_id, $filters)
            ->with('category:id,name,parent_id')
            ->get();

        $required = max(1, (int) $part->total_questions);

        if ($part->fix_category_questions) {
            return $this->selectByCategoryCounts($exam, $part, $candidates, $required, $retakeHistory);
        }

        if ($part->fix_category_marks) {
            return $this->selectByCategoryMarks($exam, $part, $candidates, $required, $retakeHistory);
        }

        // Tier 1: Primary filter matching (category + marks filter)
        $picked = $this->prioritizeCandidates($candidates, $retakeHistory, true);

        // Tier 2: Category-consistent marks relaxation if Tier 1 is insufficient
        if ($picked->count() < $required) {
            $categoryIds = $this->selectedCategoryIds($part);
            if ($categoryIds !== []) {
                $scopeIds = $this->questionBank->getDescendantCategoryIds((int) $exam->organization_id, $categoryIds);
                $relaxedFilters = array_merge($filters, ['marks' => []]);
                $relaxedCandidates = $this->questionBank
                    ->filteredQuery((int) $exam->organization_id, $relaxedFilters)
                    ->whereIn('category_id', $scopeIds)
                    ->with('category:id,name,parent_id')
                    ->get()
                    ->reject(fn (Question $q) => $picked->contains('id', $q->id));

                $relaxedPrioritized = $this->prioritizeCandidates($relaxedCandidates, $retakeHistory, true);
                foreach ($relaxedPrioritized as $q) {
                    $q->_selection_meta = array_merge($q->_selection_meta ?? [], [
                        'fallback_used' => true,
                        'fallback_type' => 'marks_relaxed',
                        'is_repeated' => false,
                    ]);
                }

                $picked = $picked->concat($relaxedPrioritized);
            }
        }

        // Tier 3: Controlled repetition if unique candidates are still fewer than required
        if ($picked->count() < $required) {
            if ($picked->isEmpty()) {
                throw new AttemptQuestionShortageException(
                    'No questions available in selected categories.',
                    [[
                        'type' => 'dynamic',
                        'part_id' => $part->id,
                        'required' => $required,
                        'available' => 0,
                        'missing' => $required,
                    ]]
                );
            }

            $picked = $this->fillRemainingWithRepetition($picked, $required, 'repeated_question');
        }

        return $picked->take($required)->values()->all();
    }

    /**
     * @param Collection<int, Question> $candidates
     * @return list<Question>
     */
    protected function selectByCategoryCounts(Exam $exam, ExamPart $part, Collection $candidates, int $required, array $retakeHistory = []): array
    {
        $allocations = $this->normalizeAllocations($part->extra_questions_allocations ?? []);
        $categoryIds = $this->selectedCategoryIds($part);
        if ($allocations === [] && $categoryIds !== []) {
            $allocations = $this->evenSplit($required, $categoryIds);
        }

        $allPicked = collect();

        foreach ($allocations as $categoryId => $count) {
            $count = max(0, (int) $count);
            if ($count === 0) {
                continue;
            }

            $scopeIds = $this->questionBank->getDescendantCategoryIds(
                (int) $exam->organization_id,
                [(int) $categoryId]
            );

            // Tier 1: Matching candidates in scope with marks filter
            $tier1Pool = $candidates
                ->filter(fn (Question $q) => in_array((int) $q->category_id, $scopeIds, true))
                ->values();

            $catPicked = $this->prioritizeCandidates($tier1Pool, $retakeHistory, true);

            // Tier 2: Marks relaxation within SAME category scope
            if ($catPicked->count() < $count) {
                $filters = array_merge($this->baseFilters($exam, $part), ['marks' => []]);
                $tier2Pool = $this->questionBank
                    ->filteredQuery((int) $exam->organization_id, $filters)
                    ->whereIn('category_id', $scopeIds)
                    ->with('category:id,name,parent_id')
                    ->get()
                    ->reject(fn (Question $q) => $catPicked->contains('id', $q->id));

                $tier2Prioritized = $this->prioritizeCandidates($tier2Pool, $retakeHistory, true);
                foreach ($tier2Prioritized as $q) {
                    $q->_selection_meta = array_merge($q->_selection_meta ?? [], [
                        'fallback_used' => true,
                        'fallback_type' => 'marks_relaxed',
                        'is_repeated' => false,
                    ]);
                }

                $catPicked = $catPicked->concat($tier2Prioritized);
            }

            // Tier 3: Controlled repetition within category
            if ($catPicked->count() < $count) {
                if ($catPicked->isEmpty()) {
                    throw new AttemptQuestionShortageException(
                        "No questions available in configured category ID {$categoryId}.",
                        [[
                            'type' => 'category_count',
                            'part_id' => $part->id,
                            'category_id' => (int) $categoryId,
                            'required' => $count,
                            'available' => 0,
                            'missing' => $count,
                        ]]
                    );
                }

                $catPicked = $this->fillRemainingWithRepetition($catPicked, $count, 'repeated_question');
            }

            $allPicked = $allPicked->merge($catPicked->take($count));
        }

        if ($allPicked->count() < $required && ! $allPicked->isEmpty()) {
            $allPicked = $this->fillRemainingWithRepetition($allPicked, $required, 'repeated_question');
        }

        return $allPicked->take($required)->values()->all();
    }

    /**
     * @param Collection<int, Question> $candidates
     * @return list<Question>
     */
    protected function selectByCategoryMarks(Exam $exam, ExamPart $part, Collection $candidates, int $required, array $retakeHistory = []): array
    {
        $allocations = $this->normalizeAllocations($part->extra_marks_allocations ?? []);
        $picked = collect();

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

            $prioritizedPool = $this->prioritizeCandidates($pool, $retakeHistory, true);
            $subset = $this->findExactMarksSubset($prioritizedPool, $marksTarget);

            if ($subset === null) {
                // Tier 2: Marks relaxation within same category
                $filters = array_merge($this->baseFilters($exam, $part), ['marks' => []]);
                $relaxedPool = $this->questionBank
                    ->filteredQuery((int) $exam->organization_id, $filters)
                    ->whereIn('category_id', $scopeIds)
                    ->with('category:id,name,parent_id')
                    ->get();

                $prioritizedRelaxed = $this->prioritizeCandidates($relaxedPool, $retakeHistory, true);
                $subset = $this->findExactMarksSubset($prioritizedRelaxed, $marksTarget);

                if ($subset !== null) {
                    foreach ($subset as $q) {
                        $q->_selection_meta = array_merge($q->_selection_meta ?? [], [
                            'fallback_used' => true,
                            'fallback_type' => 'marks_relaxed',
                            'is_repeated' => false,
                        ]);
                    }
                } else {
                    $subset = $prioritizedRelaxed->take($required);
                }
            }

            $picked = $picked->merge($subset);
        }

        if ($picked->count() < $required) {
            $remaining = $required - $picked->count();
            $leftover = $candidates
                ->reject(fn (Question $q) => $picked->contains('id', $q->id));

            $prioritizedLeftover = $this->prioritizeCandidates($leftover, $retakeHistory, true);
            $picked = $picked->merge($prioritizedLeftover->take($remaining));
        }

        if ($picked->count() < $required && ! $picked->isEmpty()) {
            $picked = $this->fillRemainingWithRepetition($picked, $required, 'repeated_question');
        }

        return $picked->take($required)->values()->all();
    }

    protected function getRetakeHistory(Exam $exam, ?User $user): array
    {
        if (! $user) {
            return [
                'attempted' => [],
                'incorrect' => [],
                'correct' => [],
            ];
        }

        $pastAttemptIds = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'graded', 'expired', 'abandoned'])
            ->pluck('id')
            ->all();

        if (empty($pastAttemptIds)) {
            return [
                'attempted' => [],
                'incorrect' => [],
                'correct' => [],
            ];
        }

        $attemptedIds = ExamAttemptQuestion::query()
            ->whereIn('exam_attempt_id', $pastAttemptIds)
            ->pluck('question_id')
            ->unique()
            ->values()
            ->all();

        $incorrectIds = ExamAttemptAnswer::query()
            ->join('exam_attempt_questions', 'exam_attempt_answers.exam_attempt_question_id', '=', 'exam_attempt_questions.id')
            ->whereIn('exam_attempt_questions.exam_attempt_id', $pastAttemptIds)
            ->where('exam_attempt_answers.is_correct', false)
            ->pluck('exam_attempt_questions.question_id')
            ->unique()
            ->values()
            ->all();

        $correctIds = ExamAttemptAnswer::query()
            ->join('exam_attempt_questions', 'exam_attempt_answers.exam_attempt_question_id', '=', 'exam_attempt_questions.id')
            ->whereIn('exam_attempt_questions.exam_attempt_id', $pastAttemptIds)
            ->where('exam_attempt_answers.is_correct', true)
            ->pluck('exam_attempt_questions.question_id')
            ->unique()
            ->values()
            ->all();

        return [
            'attempted' => $attemptedIds,
            'incorrect' => $incorrectIds,
            'correct' => $correctIds,
        ];
    }

    protected function prioritizeCandidates(Collection $candidates, array $retakeHistory, bool $shuffle = true): Collection
    {
        $attemptedMap = array_flip($retakeHistory['attempted'] ?? []);
        $incorrectMap = array_flip($retakeHistory['incorrect'] ?? []);

        $unattempted = collect();
        $incorrect = collect();
        $correct = collect();

        foreach ($candidates as $q) {
            $qId = (int) $q->id;
            $meta = $q->_selection_meta ?? [];

            if (! isset($attemptedMap[$qId])) {
                $meta['retake_priority'] = 'unattempted';
                $q->_selection_meta = $meta;
                $unattempted->push($q);
            } elseif (isset($incorrectMap[$qId])) {
                $meta['retake_priority'] = 'previously_incorrect';
                $q->_selection_meta = $meta;
                $incorrect->push($q);
            } else {
                $meta['retake_priority'] = 'previously_correct';
                $q->_selection_meta = $meta;
                $correct->push($q);
            }
        }

        if ($shuffle) {
            $unattempted = $unattempted->shuffle();
            $incorrect = $incorrect->shuffle();
            $correct = $correct->shuffle();
        }

        return $unattempted->concat($incorrect)->concat($correct);
    }

    protected function fillRemainingWithRepetition(Collection $picked, int $required, string $fallbackType = 'repeated_question'): Collection
    {
        if ($picked->isEmpty()) {
            return $picked;
        }

        $uniqueCount = $picked->count();
        $needed = $required - $uniqueCount;
        if ($needed <= 0) {
            return $picked;
        }

        $poolArray = $picked->values()->all();
        $repeatedItems = collect();

        for ($i = 0; $i < $needed; $i++) {
            /** @var Question $sourceQ */
            $sourceQ = $poolArray[$i % $uniqueCount];
            $clonedQ = clone $sourceQ;
            $clonedQ->_selection_meta = array_merge($sourceQ->_selection_meta ?? [], [
                'fallback_used' => true,
                'fallback_type' => $fallbackType,
                'is_repeated' => true,
                'source_question_id' => (int) $sourceQ->id,
            ]);
            $repeatedItems->push($clonedQ);
        }

        return $picked->concat($repeatedItems)->shuffle()->values();
    }

    /**
     * @param Collection<int, Question> $pool
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
     * @param mixed $allocations
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
     * @param list<int> $categoryIds
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
