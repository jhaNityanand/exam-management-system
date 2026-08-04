<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RespondsWithFrontendJson;
use App\Models\News;
use App\Models\NewsCategory;
use App\Services\Frontend\CategoryTreeService;
use App\Services\Frontend\DetailSidebarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    use RespondsWithFrontendJson;

    public function index(Request $request, CategoryTreeService $categoryTree): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $spotlight = $request->input('spotlight');
        $isBreaking = $request->boolean('breaking') || $spotlight === 'breaking';
        $isTrending = $request->boolean('trending') || $spotlight === 'trending';

        $query = News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->with(['category:id,name,slug', 'author:id,name', 'bannerImage', 'featuredImage', 'banners'])
            ->when($isBreaking, fn ($q) => $q->where('is_breaking', true))
            ->when($isTrending, fn ($q) => $q->where('is_trending', true))
            ->when($request->filled('category_id'), fn ($q) => $q->where('news_category_id', $request->integer('category_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('excerpt', 'like', $term)
                        ->orWhere('short_description', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            });

        match ($request->input('sort', 'latest')) {
            'oldest' => $query->oldest('published_at'),
            'title' => $query->orderBy('title')->orderByDesc('published_at'),
            'popular' => $query->orderByDesc('view_count')->orderByDesc('published_at'),
            default => $query->latest('published_at'),
        };

        $news = $query->paginate((int) $request->input('per_page', 36))->withQueryString();

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedHtmlJson($news, 'frontend.components.news-card', 'news');
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
            'filters' => $request->only(['breaking', 'trending', 'search', 'category_id', 'sort', 'spotlight']),
            'categoryNav' => $categoryTree->forNewsCategories($orgId),
        ]);
    }

    public function trending(Request $request): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $news = News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('is_trending', true)
            ->with(['category:id,name,slug', 'author:id,name', 'bannerImage', 'featuredImage', 'banners'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('excerpt', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            });

        match ($request->input('sort', 'latest')) {
            'oldest' => $news->oldest('published_at'),
            'title' => $news->orderBy('title')->orderByDesc('published_at'),
            'popular' => $news->orderByDesc('view_count')->orderByDesc('published_at'),
            default => $news->latest('published_at'),
        };

        $news = $news
            ->paginate((int) $request->input('per_page', 36))
            ->withQueryString();

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedHtmlJson($news, 'frontend.components.news-card', 'news');
        }

        return view('frontend.news.trending', [
            'news' => $news,
        ]);
    }

    public function show(News $news, DetailSidebarService $detailSidebar): View
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
            'author:id,name,slug',
            'author.profile',
            'bannerImage',
            'featuredImage',
            'banners',
            'tags:id,name,slug',
        ]);

        $news->increment('view_count');

        $relatedQuery = News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('id', '!=', $news->id);

        $tagIds = $news->tags->pluck('id');
        if ($news->news_category_id || $tagIds->isNotEmpty()) {
            $relatedQuery->where(function ($q) use ($news, $tagIds) {
                if ($news->news_category_id) {
                    $q->where('news_category_id', $news->news_category_id);
                }
                if ($tagIds->isNotEmpty()) {
                    $q->orWhereHas('tags', fn ($tags) => $tags->whereIn('news_tags.id', $tagIds));
                }
            });
        }

        $relatedNews = $relatedQuery
            ->with(['category:id,name,slug', 'bannerImage', 'featuredImage', 'banners'])
            ->latest('published_at')
            ->limit(3)
            ->get();

        $adService = app(\App\Services\Advertisement\AdvertisementService::class);
        $processedContent = $adService->injectIntoContent((string) $news->content, 'news', $orgId);

        return view('frontend.news.show', [
            'news' => $news,
            'relatedNews' => $relatedNews,
            'detailSidebar' => $detailSidebar->forNews($news, $orgId),
            'processedContent' => $processedContent,
        ]);
    }

    public function category(Request $request, string $slug, CategoryTreeService $categoryTree): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $category = NewsCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $query = News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('news_category_id', $category->id)
            ->with(['category:id,name,slug', 'author:id,name', 'bannerImage', 'featuredImage', 'banners'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('excerpt', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            });

        match ($request->input('sort', 'latest')) {
            'oldest' => $query->oldest('published_at'),
            'title' => $query->orderBy('title')->orderByDesc('published_at'),
            'popular' => $query->orderByDesc('view_count')->orderByDesc('published_at'),
            default => $query->latest('published_at'),
        };

        $news = $query
            ->paginate((int) $request->input('per_page', 36))
            ->withQueryString();

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedHtmlJson($news, 'frontend.components.news-card', 'news');
        }

        return view('frontend.news.category', [
            'category' => $category,
            'news' => $news,
            'categoryNav' => $categoryTree->forNewsCategories($orgId, (int) $category->id),
        ]);
    }

    public function tag(Request $request, string $slug): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $tag = \App\Models\NewsTag::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('slug', $slug)
            ->firstOrFail();

        $query = News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->whereHas('tags', fn ($q) => $q->where('news_tags.id', $tag->id))
            ->with(['category:id,name,slug', 'author:id,name', 'bannerImage', 'featuredImage', 'banners'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('excerpt', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            });

        match ($request->input('sort', 'latest')) {
            'oldest' => $query->oldest('published_at'),
            'title' => $query->orderBy('title')->orderByDesc('published_at'),
            'popular' => $query->orderByDesc('view_count')->orderByDesc('published_at'),
            default => $query->latest('published_at'),
        };

        $news = $query
            ->paginate((int) $request->input('per_page', 36))
            ->withQueryString();

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedHtmlJson($news, 'frontend.components.news-card', 'news');
        }

        return view('frontend.news.tag', [
            'tag' => $tag,
            'news' => $news,
        ]);
    }
}
