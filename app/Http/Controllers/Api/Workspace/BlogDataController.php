<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Support\DatatableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogDataController extends Controller
{
    private const ALLOWED_SORTS = [
        'id',
        'title',
        'status',
        'published_at',
        'view_count',
        'created_at',
        'updated_at',
        'author_name',
    ];

    private const ALLOWED_FILTERS = [
        'status',
        'blog_category_id',
        'author_id',
        'tag_id',
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
        $tagId = $filters['tag_id'] ?? null;

        $datatableFilters = array_intersect_key($filters, array_flip(self::ALLOWED_FILTERS));
        unset($datatableFilters['trash'], $datatableFilters['date_from'], $datatableFilters['date_to']);

        if (isset($datatableFilters['blog_category_id'])) {
            $datatableFilters['blog_category_id'] = $this->expandCategoryIds($datatableFilters['blog_category_id']);
            if ($datatableFilters['blog_category_id'] === []) {
                unset($datatableFilters['blog_category_id']);
            }
        }

        if (isset($datatableFilters['tag_id'])) {
            unset($datatableFilters['tag_id']);
        }

        unset($datatableFilters['trash']);
        $request->query->set('filters', $datatableFilters);

        $query = Blog::query()->forOrg($orgId);

        if ($trash) {
            $query->onlyTrashed();
        }

        $query->with(['category', 'author', 'bannerImage', 'tags']);

        if ($tagId !== null && $tagId !== '') {
            $tagIds = is_array($tagId) ? $tagId : [$tagId];
            $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));
            if ($tagIds !== []) {
                $query->whereHas('tags', fn ($q) => $q->whereIn('blog_tags.id', $tagIds));
            }
        }

        if (! empty($dateFrom)) {
            $query->whereDate('published_at', '>=', $dateFrom);
        }
        if (! empty($dateTo)) {
            $query->whereDate('published_at', '<=', $dateTo);
        }

        DatatableQuery::apply(
            $query,
            $request,
            ['title', 'excerpt', 'author_name', 'slug'],
            'id'
        );

        $paginator = $query->paginate(DatatableQuery::perPage($request));

        $data = collect($paginator->items())->map(fn (Blog $blog) => [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'excerpt' => $blog->excerpt,
            'status' => $blog->status,
            'status_label' => $blog->statusLabel(),
            'author_name' => $blog->author_name ?: $blog->author?->name,
            'author_id' => $blog->author_id,
            'blog_category_id' => $blog->blog_category_id,
            'category_name' => $blog->category?->name,
            'banner_thumbnail_url' => $blog->bannerImage?->file_url,
            'tag_names' => $blog->tags->pluck('name')->values()->all(),
            'tags' => $blog->tags,
            'published_at' => $blog->published_at?->toIso8601String(),
            'published_at_formatted' => $blog->published_at?->format('M j, Y g:i A'),
            'view_count' => (int) $blog->view_count,
            'created_at' => $blog->created_at?->toIso8601String(),
            'deleted_at' => $blog->deleted_at?->toIso8601String(),
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
            $children = BlogCategory::query()
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
