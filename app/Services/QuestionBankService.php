<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionCategory;
use App\Support\ExamFormOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class QuestionBankService
{
    public const DEFAULT_PAGE_SIZE = 50;

    public const MAX_PAGE_SIZE = 100;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(int $orgId, array $filters = []): Builder
    {
        $query = Question::query()
            ->where('organization_id', $orgId)
            ->where('status', 'active');

        $categoryIds = $this->normalizeIdList($filters['categories'] ?? null);
        if ($categoryIds !== []) {
            $descendantIds = $this->getDescendantCategoryIds($orgId, $categoryIds);
            $query->whereIn('category_id', $descendantIds);
        }

        $marks = $this->normalizeIdList($filters['marks'] ?? null);
        if ($marks !== []) {
            $query->whereIn('marks', $marks);
        }

        $formats = $this->normalizeStringList($filters['formats'] ?? null);
        if ($formats !== []) {
            $constraints = ExamFormOptions::examFormatQuestionConstraints();
            $query->where(function (Builder $q) use ($formats, $constraints) {
                foreach ($formats as $format) {
                    $rules = $constraints[$format] ?? [];
                    foreach ($rules as $rule) {
                        $q->orWhere(function (Builder $sub) use ($rule) {
                            $sub->where('type', $rule['type']);
                            if ($rule['allows_multiple'] !== null) {
                                $sub->where('allows_multiple', $rule['allows_multiple']);
                            }
                        });
                    }
                }
            });
        }

        $difficulties = $this->normalizeStringList($filters['difficulty'] ?? null);
        if ($difficulties !== []) {
            $query->whereIn('difficulty', $difficulties);
        }

        $types = $this->normalizeStringList($filters['types'] ?? null);
        if ($types !== []) {
            $query->whereIn('type', $types);
        }

        $excludeIds = $this->normalizeIdList($filters['exclude_ids'] ?? null);
        if ($excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        $ids = $this->normalizeIdList($filters['ids'] ?? null);
        if ($ids !== []) {
            $query->whereIn('id', $ids);
        }

        $keyword = trim((string) ($filters['q'] ?? ''));
        if ($keyword !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $keyword).'%';
            $query->where('body', 'like', $like);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function paginate(int $orgId, array $filters = [], ?int $cursor = null, int $perPage = self::DEFAULT_PAGE_SIZE): array
    {
        $perPage = max(1, min(self::MAX_PAGE_SIZE, $perPage));
        $base = $this->filteredQuery($orgId, $filters);
        $total = (clone $base)->count();

        $pageQuery = (clone $base)->with('category:id,name,parent_id')->orderBy('id');
        if ($cursor !== null && $cursor > 0) {
            $pageQuery->where('id', '>', $cursor);
        }

        $rows = $pageQuery
            ->limit($perPage + 1)
            ->get(['id', 'category_id', 'marks', 'difficulty', 'type', 'allows_multiple', 'body']);

        $hasMore = $rows->count() > $perPage;
        if ($hasMore) {
            $rows = $rows->take($perPage);
        }

        $data = $rows->map(fn (Question $q) => $this->formatQuestion($q))->values()->all();
        $nextCursor = $hasMore && $rows->isNotEmpty() ? (int) $rows->last()->id : null;

        return [
            'data' => $data,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'next_cursor' => $nextCursor,
                'has_more' => $hasMore,
                'loaded' => count($data),
            ],
        ];
    }

    /**
     * Count matching questions for each selected category bucket (includes descendants).
     *
     * @param  array<string, mixed>  $filters
     * @param  list<int|string>  $bucketCategoryIds
     * @return array{data: array<string, int>, meta: array{total: int}}
     */
    public function countsByCategory(int $orgId, array $filters, array $bucketCategoryIds): array
    {
        $buckets = array_values(array_unique(array_filter(array_map('intval', $bucketCategoryIds), static fn (int $id) => $id > 0)));
        $counts = [];
        $total = 0;

        foreach ($buckets as $bucketId) {
            $bucketFilters = $filters;
            $bucketFilters['categories'] = [$bucketId];
            $count = $this->filteredQuery($orgId, $bucketFilters)->count();
            $counts[(string) $bucketId] = $count;
            $total += $count;
        }

        return [
            'data' => $counts,
            'meta' => [
                'total' => $total,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int|string, int>  $categoryQuotas
     * @return list<array<string, mixed>>
     */
    public function randomSample(int $orgId, array $filters, int $count, array $categoryQuotas = []): array
    {
        $count = max(0, $count);
        if ($count === 0) {
            return [];
        }

        if ($categoryQuotas !== []) {
            $picked = collect();
            foreach ($categoryQuotas as $categoryId => $quota) {
                $quota = max(0, (int) $quota);
                if ($quota === 0) {
                    continue;
                }
                $categoryFilters = $filters;
                $categoryFilters['categories'] = [(int) $categoryId];
                $ids = $this->filteredQuery($orgId, $categoryFilters)
                    ->inRandomOrder()
                    ->limit($quota)
                    ->pluck('id')
                    ->all();
                $picked = $picked->merge($ids);
            }

            $selectedIds = $picked->unique()->values()->all();
        } else {
            $selectedIds = $this->filteredQuery($orgId, $filters)
                ->inRandomOrder()
                ->limit($count)
                ->pluck('id')
                ->all();
        }

        if ($selectedIds === []) {
            return [];
        }

        return Question::query()
            ->where('organization_id', $orgId)
            ->whereIn('id', $selectedIds)
            ->with('category:id,name,parent_id')
            ->get(['id', 'category_id', 'marks', 'difficulty', 'type', 'allows_multiple', 'body'])
            ->shuffle()
            ->map(fn (Question $q) => $this->formatQuestion($q))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function formatQuestion(Question $q): array
    {
        return [
            'id' => $q->id,
            'categoryId' => (string) $q->category_id,
            'marks' => $q->marks,
            'difficulty' => $q->difficulty,
            'type' => $q->type,
            'allowsMultiple' => (bool) $q->allows_multiple,
            'text' => strip_tags((string) $q->body),
        ];
    }

    /**
     * @param  list<int|string>  $categoryIds
     * @return list<int>
     */
    public function getDescendantCategoryIds(int $orgId, array $categoryIds): array
    {
        $allIds = array_values(array_unique(array_map('intval', $categoryIds)));
        $toProcess = $allIds;

        while ($toProcess !== []) {
            $childrenIds = QuestionCategory::query()
                ->where('organization_id', $orgId)
                ->whereIn('parent_id', $toProcess)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $newChildren = array_values(array_diff($childrenIds, $allIds));
            $allIds = array_values(array_unique(array_merge($allIds, $newChildren)));
            $toProcess = $newChildren;
        }

        return $allIds;
    }

    /**
     * @param  mixed  $value
     * @return list<int>
     */
    protected function normalizeIdList(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $value), static fn (int $id) => $id > 0)));
    }

    /**
     * @param  mixed  $value
     * @return list<string>
     */
    protected function normalizeStringList(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($item) => trim((string) $item),
            $value
        ), static fn (string $item) => $item !== ''));
    }
}
