<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RespondsWithFrontendJson;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    use RespondsWithFrontendJson;

    public function index(Request $request): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $blogs = Blog::query()
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
            ->latest('published_at')
            ->paginate((int) $request->input('per_page', 12))
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

    public function show(Blog $blog): View
    {
        $orgId = $this->organizationId();

        abort_unless(
            $blog->status === Blog::STATUS_PUBLISHED
                && ($orgId === null || (int) $blog->organization_id === $orgId),
            404
        );

        $blog->load([
            'category:id,name,slug',
            'author:id,name',
            'bannerImage',
            'banners',
            'tags:id,name,slug',
        ]);

        $blog->increment('view_count');

        $relatedBlogs = Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('id', '!=', $blog->id)
            ->where(function ($q) use ($blog) {
                if ($blog->blog_category_id) {
                    $q->where('blog_category_id', $blog->blog_category_id);
                }
                $tagIds = $blog->tags->pluck('id');
                if ($tagIds->isNotEmpty()) {
                    $q->orWhereHas('tags', fn ($tags) => $tags->whereIn('blog_tags.id', $tagIds));
                }
            })
            ->with(['category:id,name,slug', 'bannerImage', 'banners'])
            ->latest('published_at')
            ->limit(4)
            ->get();

        return view('frontend.blog.show', [
            'blog' => $blog,
            'relatedBlogs' => $relatedBlogs,
        ]);
    }

    public function category(Request $request, string $slug): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $category = BlogCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('slug', $slug)
            ->firstOrFail();

        $blogs = Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('blog_category_id', $category->id)
            ->with(['category:id,name,slug', 'author:id,name', 'bannerImage', 'banners'])
            ->latest('published_at')
            ->paginate((int) $request->input('per_page', 12))
            ->withQueryString();

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedHtmlJson($blogs, 'frontend.components.blog-card', 'blog');
        }

        return view('frontend.blog.category', [
            'category' => $category,
            'blogs' => $blogs,
        ]);
    }

    public function tag(Request $request, string $slug): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $tag = BlogTag::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('slug', $slug)
            ->firstOrFail();

        $blogs = Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->whereHas('tags', fn ($q) => $q->where('blog_tags.id', $tag->id))
            ->with(['category:id,name,slug', 'author:id,name', 'bannerImage', 'banners'])
            ->latest('published_at')
            ->paginate((int) $request->input('per_page', 12))
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
