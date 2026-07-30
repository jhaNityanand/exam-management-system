<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RespondsWithFrontendJson;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use RespondsWithFrontendJson;

    public function index(Request $request): View|JsonResponse
    {
        $orgId = $this->organizationId();
        $items = $this->catalogItems($orgId);

        $type = $request->string('type')->toString();
        if (in_array($type, ['exams', 'blogs', 'news'], true)) {
            $items = $items->where('type', $type)->values();
        }

        if ($request->filled('search')) {
            $term = Str::lower($request->string('search')->trim()->toString());
            $items = $items->filter(function ($item) use ($term) {
                return Str::contains(Str::lower((string) $item->name), $term)
                    || Str::contains(Str::lower((string) ($item->description ?? '')), $term);
            })->values();
        }

        $sort = $request->input('sort', 'name');
        $items = match ($sort) {
            'name_desc' => $items->sortByDesc(fn ($item) => Str::lower((string) $item->name))->values(),
            default => $items->sortBy(fn ($item) => Str::lower((string) $item->name))->values(),
        };

        $perPage = max(1, (int) $request->input('per_page', 36));
        $page = max(1, (int) $request->input('page', 1));
        $total = $items->count();
        $categories = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedHtmlJson($categories, 'frontend.components.catalog-category-card', 'item');
        }

        return view('frontend.category.index', [
            'categories' => $categories,
        ]);
    }

    public function show(Request $request, ExamCategory $category): View|JsonResponse
    {
        $orgId = $this->organizationId();

        abort_unless(
            $category->status === 'active'
                && ($orgId === null || (int) $category->organization_id === $orgId),
            404
        );

        $category->load([
            'children' => fn ($q) => $q->where('status', 'active')->orderBy('sort_order')->orderBy('name'),
            'parent:id,name,slug',
            'ogImage',
        ]);

        $exams = Exam::query()
            ->publicCatalog()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('category_id', $category->id)
            ->with(['category:id,name,slug'])
            ->latest('id')
            ->paginate((int) $request->input('per_page', 36), ['*'], 'page')
            ->withQueryString();

        $relatedBlogs = $this->relatedBlogsForCategorySlug($orgId, $category->slug);
        $relatedNews = $this->relatedNewsForCategorySlug($orgId, $category->slug);

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedHtmlJson($exams, 'frontend.components.exam-card', 'exam');
        }

        return view('frontend.category.show', [
            'category' => $category,
            'exams' => $exams,
            'relatedBlogs' => $relatedBlogs,
            'relatedNews' => $relatedNews,
        ]);
    }

    /**
     * @return Collection<int, object{name:string,description:?string,url:string,type:string,type_label:string}>
     */
    protected function catalogItems(?int $orgId): Collection
    {
        $items = collect();

        ExamCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->roots()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description'])
            ->each(function (ExamCategory $category) use ($items) {
                $items->push((object) [
                    'name' => $category->name,
                    'description' => $category->description,
                    'url' => route('frontend.categories.show', $category->slug),
                    'type' => 'exams',
                    'type_label' => 'Exam',
                ]);
            });

        BlogCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->roots()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description'])
            ->each(function (BlogCategory $category) use ($items) {
                $items->push((object) [
                    'name' => $category->name,
                    'description' => $category->description,
                    'url' => route('frontend.blogs.category', $category->slug),
                    'type' => 'blogs',
                    'type_label' => 'Blog',
                ]);
            });

        NewsCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->roots()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description'])
            ->each(function (NewsCategory $category) use ($items) {
                $items->push((object) [
                    'name' => $category->name,
                    'description' => $category->description,
                    'url' => route('frontend.news.category', $category->slug),
                    'type' => 'news',
                    'type_label' => 'News',
                ]);
            });

        return $items;
    }

    protected function relatedBlogsForCategorySlug(?int $orgId, string $slug): Collection
    {
        $blogCategory = BlogCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (! $blogCategory) {
            return collect();
        }

        return Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('blog_category_id', $blogCategory->id)
            ->with(['category:id,name,slug', 'bannerImage', 'banners'])
            ->latest('published_at')
            ->limit(6)
            ->get();
    }

    protected function relatedNewsForCategorySlug(?int $orgId, string $slug): Collection
    {
        $newsCategory = NewsCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (! $newsCategory) {
            return collect();
        }

        return News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('news_category_id', $newsCategory->id)
            ->with(['category:id,name,slug', 'bannerImage', 'featuredImage'])
            ->latest('published_at')
            ->limit(6)
            ->get();
    }
}
