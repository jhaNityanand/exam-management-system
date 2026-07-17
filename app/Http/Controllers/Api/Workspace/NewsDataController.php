<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\NewsCategory;
use App\Support\DatatableQuery;
use App\Support\DateRangeFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsDataController extends Controller
{
    private const ALLOWED_SORTS = [
        'id',
        'title',
        'status',
        'published_at',
        'expires_at',
        'view_count',
        'sort_order',
        'created_at',
        'updated_at',
        'author_name',
    ];

    private const ALLOWED_FILTERS = [
        'status',
        'news_category_id',
        'author_id',
        'tag_id',
        'visibility',
        'is_featured',
        'is_breaking',
        'is_trending',
        'trash',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $orgId = current_organization_id();
        abort_if($orgId === null, 503, 'No organization found. Please run the database seeder.');

        $sort = (string) $request->query('sort', 'id');
        if (! in_array($sort, self::ALLOWED_SORTS, true)) {
            $request->query->set('sort', 'id');
        }

        $filters = $request->query('filters', []);
        if (! is_array($filters)) {
            $filters = [];
        }

        $trash = ($filters['trash'] ?? 'active') === 'bin';
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $createdFrom = $filters['created_from'] ?? null;
        $createdTo = $filters['created_to'] ?? null;
        $tagId = $filters['tag_id'] ?? null;

        $datatableFilters = array_intersect_key($filters, array_flip(self::ALLOWED_FILTERS));
        unset($datatableFilters['trash'], $datatableFilters['date_from'], $datatableFilters['date_to']);

        if (isset($datatableFilters['news_category_id'])) {
            $datatableFilters['news_category_id'] = $this->expandCategoryIds($datatableFilters['news_category_id']);
            if ($datatableFilters['news_category_id'] === []) {
                unset($datatableFilters['news_category_id']);
            }
        }

        unset($datatableFilters['tag_id'], $datatableFilters['trash']);

        foreach (['is_featured', 'is_breaking', 'is_trending'] as $flag) {
            if (! array_key_exists($flag, $datatableFilters) || $datatableFilters[$flag] === '' || $datatableFilters[$flag] === null) {
                unset($datatableFilters[$flag]);

                continue;
            }

            $values = is_array($datatableFilters[$flag])
                ? $datatableFilters[$flag]
                : [$datatableFilters[$flag]];
            $values = array_values(array_unique(array_map(
                'intval',
                array_filter($values, fn ($value) => in_array((string) $value, ['0', '1'], true))
            )));

            if ($values === []) {
                unset($datatableFilters[$flag]);
            } else {
                $datatableFilters[$flag] = count($values) === 1 ? $values[0] : $values;
            }
        }

        $request->query->set('filters', $datatableFilters);

        $query = News::query()->forOrg($orgId);

        if ($trash) {
            $query->onlyTrashed();
        }

        $query->with(['category', 'author', 'bannerImage', 'featuredImage', 'tags']);

        if ($tagId !== null && $tagId !== '') {
            $tagIds = is_array($tagId) ? $tagId : [$tagId];
            $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));
            if ($tagIds !== []) {
                $query->whereHas('tags', fn ($q) => $q->whereIn('news_tags.id', $tagIds));
            }
        }

        DateRangeFilter::apply($query, 'published_at', $dateFrom, $dateTo);
        DateRangeFilter::apply($query, 'created_at', $createdFrom, $createdTo);

        DatatableQuery::apply(
            $query,
            $request,
            ['title', 'excerpt', 'short_description', 'author_name', 'slug'],
            'id'
        );

        $paginator = $query->paginate(DatatableQuery::perPage($request));

        $data = collect($paginator->items())->map(fn (News $news) => [
            'id' => $news->id,
            'title' => $news->title,
            'slug' => $news->slug,
            'excerpt' => $news->excerpt,
            'short_description' => $news->short_description,
            'status' => $news->status,
            'status_label' => $news->statusLabel(),
            'visibility' => $news->visibility,
            'visibility_label' => $news->visibilityLabel(),
            'is_featured' => (bool) $news->is_featured,
            'is_breaking' => (bool) $news->is_breaking,
            'is_trending' => (bool) $news->is_trending,
            'author_name' => $news->author_name ?: $news->author?->name,
            'author_id' => $news->author_id,
            'news_category_id' => $news->news_category_id,
            'category_name' => $news->category?->name,
            'banner_thumbnail_url' => $news->bannerImage?->thumbnail_url
                ?? $news->bannerImage?->file_url
                ?? $news->featuredImage?->thumbnail_url
                ?? $news->featuredImage?->file_url,
            'tag_names' => $news->tags->pluck('name')->values()->all(),
            'tags' => $news->tags,
            'published_at' => $news->published_at?->toIso8601String(),
            'published_at_formatted' => $news->published_at?->format('M j, Y g:i A'),
            'view_count' => (int) $news->view_count,
            'sort_order' => (int) $news->sort_order,
            'created_at' => $news->created_at?->toIso8601String(),
            'deleted_at' => $news->deleted_at?->toIso8601String(),
        ])->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * @param  int|list<int>|string  $categoryIds
     * @return int|list<int>
     */
    protected function expandCategoryIds(int|array|string $categoryIds): int|array
    {
        $ids = is_array($categoryIds) ? $categoryIds : [$categoryIds];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return [];
        }

        $expanded = $ids;
        $toProcess = $ids;

        while ($toProcess !== []) {
            $children = NewsCategory::query()
                ->whereIn('parent_id', $toProcess)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $new = array_values(array_diff($children, $expanded));
            $expanded = array_values(array_unique(array_merge($expanded, $new)));
            $toProcess = $new;
        }

        return count($expanded) === 1 ? $expanded[0] : $expanded;
    }
}
