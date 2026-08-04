<?php

namespace App\Services\Frontend;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Question;
use App\Models\QuestionCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Builds nested category navigation trees for frontend listing sidebars.
 * Loads categories and item counts in bulk to avoid N+1 queries.
 */
class CategoryTreeService
{
    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     context_name: string|null,
     *     active_id: int|null,
     *     expanded_ids: list<int>,
     *     roots: list<array<string, mixed>>
     * }
     */
    public function forBlogCategories(?int $orgId, ?int $activeCategoryId = null): array
    {
        return $this->build(
            activeCategoryId: $activeCategoryId,
            categoryQuery: BlogCategory::query()
                ->when($orgId, fn ($q) => $q->forOrg($orgId))
                ->where('status', 'active'),
            countQuery: Blog::query()
                ->published()
                ->when($orgId, fn ($q) => $q->forOrg($orgId))
                ->whereNotNull('blog_category_id'),
            countColumn: 'blog_category_id',
            urlResolver: fn (Model $category) => route('frontend.blogs.category', $category->slug),
        );
    }

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     context_name: string|null,
     *     active_id: int|null,
     *     expanded_ids: list<int>,
     *     roots: list<array<string, mixed>>
     * }
     */
    public function forNewsCategories(?int $orgId, ?int $activeCategoryId = null): array
    {
        return $this->build(
            activeCategoryId: $activeCategoryId,
            categoryQuery: NewsCategory::query()
                ->when($orgId, fn ($q) => $q->forOrg($orgId))
                ->where('status', 'active'),
            countQuery: News::query()
                ->published()
                ->when($orgId, fn ($q) => $q->forOrg($orgId))
                ->whereNotNull('news_category_id'),
            countColumn: 'news_category_id',
            urlResolver: fn (Model $category) => route('frontend.news.category', $category->slug),
        );
    }

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     context_name: string|null,
     *     active_id: int|null,
     *     expanded_ids: list<int>,
     *     roots: list<array<string, mixed>>
     * }
     */
    public function forQuestionCategories(?int $orgId, ?int $activeCategoryId = null): array
    {
        return $this->build(
            activeCategoryId: $activeCategoryId,
            categoryQuery: QuestionCategory::query()
                ->publiclyVisible()
                ->when($orgId, fn ($q) => $q->forOrg($orgId)),
            countQuery: Question::query()
                ->publiclyVisible()
                ->when($orgId, fn ($q) => $q->forOrg($orgId))
                ->whereNotNull('category_id'),
            countColumn: 'category_id',
            urlResolver: fn (Model $category) => route('frontend.questions.category', $category->slug),
        );
    }

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     context_name: string|null,
     *     active_id: int|null,
     *     expanded_ids: list<int>,
     *     roots: list<array<string, mixed>>
     * }
     */
    public function forExamCategories(?int $orgId, ?int $activeCategoryId = null): array
    {
        return $this->build(
            activeCategoryId: $activeCategoryId,
            categoryQuery: ExamCategory::query()
                ->when($orgId, fn ($q) => $q->forOrg($orgId))
                ->where('status', 'active'),
            countQuery: Exam::query()
                ->publicCatalog()
                ->when($orgId, fn ($q) => $q->forOrg($orgId))
                ->whereNotNull('category_id'),
            countColumn: 'category_id',
            urlResolver: fn (Model $category) => route('frontend.categories.show', $category->slug),
        );
    }

    /**
     * Subcategory tree for the active category (children + nested descendants).
     * Categories with zero items (including descendants) are omitted.
     *
     * @param  callable(Model): string  $urlResolver
     * @return array{
     *     title: string,
     *     description: string,
     *     context_name: string|null,
     *     active_id: int|null,
     *     expanded_ids: list<int>,
     *     roots: list<array<string, mixed>>
     * }
     */
    protected function build(
        ?int $activeCategoryId,
        Builder $categoryQuery,
        Builder $countQuery,
        string $countColumn,
        callable $urlResolver,
    ): array {
        $categories = $categoryQuery
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'parent_id', 'sort_order']);

        $directCounts = $countQuery
            ->selectRaw($countColumn.', COUNT(*) as aggregate')
            ->groupBy($countColumn)
            ->pluck('aggregate', $countColumn);

        $counts = $this->inclusiveCounts($categories, $directCounts);

        $context = $activeCategoryId
            ? $categories->firstWhere('id', $activeCategoryId)
            : null;

        $roots = [];
        if ($activeCategoryId !== null) {
            $roots = $this->nest(
                categories: $categories,
                counts: $counts,
                activeId: $activeCategoryId,
                urlResolver: $urlResolver,
                parentId: $activeCategoryId,
                expandRoots: true,
            );
            $roots = $this->pruneEmpty($roots);
        }

        return [
            'title' => 'Subcategories',
            'description' => $context
                ? 'Browse nested topics under '.$context->name.'.'
                : 'Browse nested topics in this category.',
            'context_name' => $context?->name,
            'active_id' => $activeCategoryId,
            'expanded_ids' => [],
            'roots' => $roots,
        ];
    }

    /**
     * @param  Collection<int, Model>  $categories
     * @param  Collection<int|string, int|string>  $directCounts
     * @return Collection<int, int>
     */
    protected function inclusiveCounts(Collection $categories, Collection $directCounts): Collection
    {
        $childrenMap = $categories->groupBy(fn (Model $category) => $category->parent_id);
        $memo = [];

        $compute = function (int $id) use (&$compute, &$memo, $directCounts, $childrenMap): int {
            if (array_key_exists($id, $memo)) {
                return $memo[$id];
            }

            $total = (int) ($directCounts[$id] ?? 0);
            foreach ($childrenMap->get($id, collect()) as $child) {
                $total += $compute((int) $child->id);
            }

            return $memo[$id] = $total;
        };

        $result = collect();
        foreach ($categories as $category) {
            $result[(int) $category->id] = $compute((int) $category->id);
        }

        return $result;
    }

    /**
     * @param  Collection<int, Model>  $categories
     * @param  Collection<int, int>  $counts
     * @param  callable(Model): string  $urlResolver
     * @return list<array<string, mixed>>
     */
    protected function nest(
        Collection $categories,
        Collection $counts,
        ?int $activeId,
        callable $urlResolver,
        ?int $parentId = null,
        bool $expandRoots = false,
    ): array {
        return $categories
            ->filter(fn (Model $category) => (int) ($category->parent_id ?? 0) === (int) ($parentId ?? 0))
            ->values()
            ->map(function (Model $category) use ($categories, $counts, $activeId, $urlResolver, $expandRoots) {
                $id = (int) $category->id;
                $children = $this->nest(
                    categories: $categories,
                    counts: $counts,
                    activeId: $activeId,
                    urlResolver: $urlResolver,
                    parentId: $id,
                    expandRoots: false,
                );

                return [
                    'id' => $id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'url' => $urlResolver($category),
                    'count' => (int) ($counts[$id] ?? 0),
                    'is_active' => $activeId !== null && $id === $activeId,
                    'is_expanded' => $children !== [] && $expandRoots,
                    'has_children' => $children !== [],
                    'children' => $children,
                ];
            })
            ->all();
    }

    /**
     * Drop nodes whose inclusive item count is zero.
     *
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    protected function pruneEmpty(array $nodes): array
    {
        $kept = [];

        foreach ($nodes as $node) {
            $children = $this->pruneEmpty($node['children'] ?? []);
            $count = (int) ($node['count'] ?? 0);

            if ($count < 1 && $children === []) {
                continue;
            }

            $node['children'] = $children;
            $node['has_children'] = $children !== [];
            $node['is_expanded'] = $node['has_children'] && (bool) ($node['is_expanded'] ?? false);
            $kept[] = $node;
        }

        return $kept;
    }
}
