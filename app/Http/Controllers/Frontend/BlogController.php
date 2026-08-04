<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RespondsWithFrontendJson;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Services\Frontend\CategoryTreeService;
use App\Services\Frontend\DetailSidebarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    use RespondsWithFrontendJson;

    public function index(Request $request): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $query = Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->with(['category:id,name,slug', 'author:id,name', 'bannerImage', 'banners'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('excerpt', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            })
            ->when($request->filled('category_id'), fn ($q) => $q->where('blog_category_id', $request->integer('category_id')));

        match ($request->input('sort', 'latest')) {
            'oldest' => $query->oldest('published_at'),
            'title' => $query->orderBy('title')->orderByDesc('published_at'),
            'popular' => $query->orderByDesc('view_count')->orderByDesc('published_at'),
            default => $query->latest('published_at'),
        };

        $blogs = $query
            ->paginate((int) $request->input('per_page', 36))
            ->withQueryString();

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedHtmlJson($blogs, 'frontend.components.blog-card', 'blog');
        }

        $categories = BlogCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->roots()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('frontend.blog.index', [
            'blogs' => $blogs,
            'categories' => $categories,
        ]);
    }

    public function show(Blog $blog, DetailSidebarService $detailSidebar): View
    {
        $orgId = $this->organizationId();

        abort_unless(
            $blog->status === Blog::STATUS_PUBLISHED
                && ($orgId === null || (int) $blog->organization_id === $orgId),
            404
        );

        $blog->load([
            'category:id,name,slug',
            'author:id,name,slug',
            'author.profile',
            'bannerImage',
            'banners',
            'tags:id,name,slug',
        ]);

        $blog->increment('view_count');

        $relatedQuery = Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('id', '!=', $blog->id);

        $tagIds = $blog->tags->pluck('id');
        if ($blog->blog_category_id || $tagIds->isNotEmpty()) {
            $relatedQuery->where(function ($q) use ($blog, $tagIds) {
                if ($blog->blog_category_id) {
                    $q->where('blog_category_id', $blog->blog_category_id);
                }
                if ($tagIds->isNotEmpty()) {
                    $q->orWhereHas('tags', fn ($tags) => $tags->whereIn('blog_tags.id', $tagIds));
                }
            });
        }

        $relatedBlogs = $relatedQuery
            ->with(['category:id,name,slug', 'bannerImage', 'banners'])
            ->latest('published_at')
            ->limit(3)
            ->get();

        $adService = app(\App\Services\Advertisement\AdvertisementService::class);
        $processedContent = $adService->injectIntoContent((string) $blog->content, 'blog', $orgId);

        return view('frontend.blog.show', [
            'blog' => $blog,
            'relatedBlogs' => $relatedBlogs,
            'detailSidebar' => $detailSidebar->forBlog($blog, $orgId),
            'processedContent' => $processedContent,
        ]);
    }

    public function category(Request $request, string $slug, CategoryTreeService $categoryTree): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $category = BlogCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $query = Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('blog_category_id', $category->id)
            ->with(['category:id,name,slug', 'author:id,name', 'bannerImage', 'banners'])
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

        $blogs = $query
            ->paginate((int) $request->input('per_page', 36))
            ->withQueryString();

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedHtmlJson($blogs, 'frontend.components.blog-card', 'blog');
        }

        return view('frontend.blog.category', [
            'category' => $category,
            'blogs' => $blogs,
            'categoryNav' => $categoryTree->forBlogCategories($orgId, (int) $category->id),
        ]);
    }

    public function tag(Request $request, string $slug): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $tag = BlogTag::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('slug', $slug)
            ->firstOrFail();

        $query = Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->whereHas('tags', fn ($q) => $q->where('blog_tags.id', $tag->id))
            ->with(['category:id,name,slug', 'author:id,name', 'bannerImage', 'banners'])
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

        $blogs = $query
            ->paginate((int) $request->input('per_page', 36))
            ->withQueryString();

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedHtmlJson($blogs, 'frontend.components.blog-card', 'blog');
        }

        return view('frontend.blog.tag', [
            'tag' => $tag,
            'blogs' => $blogs,
        ]);
    }
}
