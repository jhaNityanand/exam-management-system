<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RespondsWithFrontendJson;
use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    use RespondsWithFrontendJson;

    public function index(Request $request): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $query = News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->with(['category:id,name,slug', 'author:id,name', 'bannerImage', 'featuredImage'])
            ->when($request->boolean('breaking'), fn ($q) => $q->where('is_breaking', true))
            ->when($request->boolean('trending'), fn ($q) => $q->where('is_trending', true))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('excerpt', 'like', $term)
                        ->orWhere('short_description', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            })
            ->latest('published_at');

        $news = $query->paginate((int) $request->input('per_page', 12))->withQueryString();

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedJson($news);
        }

        $categories = NewsCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->roots()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('frontend.news.index', [
            'news' => $news,
            'categories' => $categories,
            'filters' => $request->only(['breaking', 'trending', 'search']),
        ]);
    }

    public function trending(Request $request): View|JsonResponse
    {
        $request->merge(['trending' => true]);

        return $this->index($request);
    }

    public function show(News $news): View
    {
        $orgId = $this->organizationId();

        abort_unless(
            $news->status === News::STATUS_PUBLISHED
                && $news->visibility === News::VISIBILITY_PUBLIC
                && ($orgId === null || (int) $news->organization_id === $orgId),
            404
        );

        $news->load([
            'category:id,name,slug',
            'author:id,name',
            'bannerImage',
            'featuredImage',
            'tags:id,name,slug',
        ]);

        $relatedNews = News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('id', '!=', $news->id)
            ->when($news->news_category_id, fn ($q) => $q->where('news_category_id', $news->news_category_id))
            ->with(['category:id,name,slug', 'bannerImage', 'featuredImage'])
            ->latest('published_at')
            ->limit(4)
            ->get();

        return view('frontend.news.show', [
            'news' => $news,
            'relatedNews' => $relatedNews,
        ]);
    }

    public function category(Request $request, string $slug): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $category = NewsCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('slug', $slug)
            ->firstOrFail();

        $news = News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('news_category_id', $category->id)
            ->with(['category:id,name,slug', 'author:id,name', 'bannerImage', 'featuredImage'])
            ->latest('published_at')
            ->paginate((int) $request->input('per_page', 12))
            ->withQueryString();

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedJson($news);
        }

        return view('frontend.news.category', [
            'category' => $category,
            'news' => $news,
        ]);
    }
}
