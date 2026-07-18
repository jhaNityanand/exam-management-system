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
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use RespondsWithFrontendJson;

    public function index(Request $request): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $examCategories = ExamCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->roots()
            ->with(['children' => fn ($q) => $q->where('status', 'active')->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $blogCategories = BlogCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->roots()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description']);

        $newsCategories = NewsCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->roots()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description']);

        if ($this->wantsFrontendJson($request)) {
            return response()->json([
                'data' => [
                    'exams' => $examCategories,
                    'blogs' => $blogCategories,
                    'news' => $newsCategories,
                ],
                'meta' => [
                    'exam_count' => $examCategories->count(),
                    'blog_count' => $blogCategories->count(),
                    'news_count' => $newsCategories->count(),
                ],
            ]);
        }

        return view('frontend.category.index', [
            'examCategories' => $examCategories,
            'blogCategories' => $blogCategories,
            'newsCategories' => $newsCategories,
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
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('category_id', $category->id)
            ->with(['category:id,name,slug'])
            ->latest('id')
            ->paginate((int) $request->input('per_page', 12), ['*'], 'page')
            ->withQueryString();

        $relatedBlogs = $this->relatedBlogsForCategorySlug($orgId, $category->slug);
        $relatedNews = $this->relatedNewsForCategorySlug($orgId, $category->slug);

        if ($this->wantsFrontendJson($request)) {
            return response()->json([
                'data' => [
                    'category' => $category,
                    'exams' => $exams->items(),
                    'blogs' => $relatedBlogs,
                    'news' => $relatedNews,
                ],
                'meta' => [
                    'current_page' => $exams->currentPage(),
                    'last_page' => $exams->lastPage(),
                    'per_page' => $exams->perPage(),
                    'total' => $exams->total(),
                    'from' => $exams->firstItem(),
                    'to' => $exams->lastItem(),
                ],
            ]);
        }

        return view('frontend.category.show', [
            'category' => $category,
            'exams' => $exams,
            'relatedBlogs' => $relatedBlogs,
            'relatedNews' => $relatedNews,
        ]);
    }

    /**
     * @return Collection<int, Blog>
     */
    protected function relatedBlogsForCategorySlug(?int $orgId, ?string $slug): Collection
    {
        if (! $slug) {
            return collect();
        }

        $blogCategory = BlogCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('slug', $slug)
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

    /**
     * @return Collection<int, News>
     */
    protected function relatedNewsForCategorySlug(?int $orgId, ?string $slug): Collection
    {
        if (! $slug) {
            return collect();
        }

        $newsCategory = NewsCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('slug', $slug)
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
