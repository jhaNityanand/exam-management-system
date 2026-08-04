<?php

namespace App\Services\Frontend;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Builds the right-rail payload for blog/news detail pages:
 * author, tags, related categories, and random latest posts.
 */
class ArticleAsideService
{
    public const CATEGORY_LIMIT = 3;

    public const LATEST_LIMIT = 3;

    public const LATEST_POOL = 18;

    /**
     * @return array{
     *     type: string,
     *     author: array<string, mixed>,
     *     tags: list<array{name: string, url: string}>,
     *     categories: list<array{name: string, url: string, description: ?string, is_current: bool}>,
     *     latest: list<array{url: string, title: string, image: ?string, kicker: ?string, meta: ?string}>
     * }
     */
    public function forBlog(Blog $blog, ?int $orgId): array
    {
        $blog->loadMissing([
            'category:id,name,slug,description,parent_id',
            'category.parent:id,name,slug,description,parent_id',
            'author:id,name,slug',
            'author.profile',
            'tags:id,name,slug',
        ]);

        return [
            'type' => 'blog',
            'author' => $this->authorPayload(
                user: $blog->author,
                displayName: $blog->author_name ?: ($blog->author->name ?? null),
                fallbackName: 'Examtube Editorial',
                defaultBio: 'Sharing practice insights, guides, and learning updates on Examtube.',
                publishedLabel: $blog->published_at
                    ? 'Published '.$blog->published_at->format('d M Y')
                    : null,
            ),
            'tags' => $blog->tags
                ->map(fn ($tag) => [
                    'name' => $tag->name,
                    'url' => route('frontend.blogs.tag', $tag->slug),
                ])
                ->values()
                ->all(),
            'categories' => $this->relatedCategories(
                category: $blog->category,
                orgId: $orgId,
                modelClass: BlogCategory::class,
                urlResolver: fn (BlogCategory $category) => route('frontend.blogs.category', $category->slug),
            ),
            'latest' => $this->latestBlogs($blog, $orgId),
        ];
    }

    /**
     * @return array{
     *     type: string,
     *     author: array<string, mixed>,
     *     tags: list<array{name: string, url: string}>,
     *     categories: list<array{name: string, url: string, description: ?string, is_current: bool}>,
     *     latest: list<array{url: string, title: string, image: ?string, kicker: ?string, meta: ?string}>
     * }
     */
    public function forNews(News $news, ?int $orgId): array
    {
        $news->loadMissing([
            'category:id,name,slug,description,parent_id',
            'category.parent:id,name,slug,description,parent_id',
            'author:id,name,slug',
            'author.profile',
            'tags:id,name,slug',
        ]);

        return [
            'type' => 'news',
            'author' => $this->authorPayload(
                user: $news->author,
                displayName: $news->author_name ?: ($news->author->name ?? null),
                fallbackName: 'News Desk',
                defaultBio: 'Covering exam alerts, campus updates, and aspirant-focused news on Examtube.',
                publishedLabel: $news->published_at
                    ? 'Published '.$news->published_at->format('d M Y, H:i')
                    : null,
            ),
            'tags' => $news->tags
                ->map(fn ($tag) => [
                    'name' => $tag->name,
                    'url' => route('frontend.news.tag', $tag->slug),
                ])
                ->values()
                ->all(),
            'categories' => $this->relatedCategories(
                category: $news->category,
                orgId: $orgId,
                modelClass: NewsCategory::class,
                urlResolver: fn (NewsCategory $category) => route('frontend.news.category', $category->slug),
            ),
            'latest' => $this->latestNews($news, $orgId),
        ];
    }

    /**
     * @return array{
     *     name: string,
     *     bio: string,
     *     avatar_url: ?string,
     *     initials: string,
     *     color: string,
     *     profile_url: ?string,
     *     published_label: ?string
     * }
     */
    protected function authorPayload(
        mixed $user,
        ?string $displayName,
        string $fallbackName,
        string $defaultBio,
        ?string $publishedLabel,
    ): array {
        $name = trim((string) ($displayName ?: ($user->name ?? '')));
        if ($name === '') {
            $name = $fallbackName;
        }

        $avatar = function_exists('user_avatar')
            ? user_avatar($user, $name)
            : ['url' => null, 'initials' => strtoupper(substr($name, 0, 1)), 'color' => '#0f766e'];

        $bio = trim((string) ($user?->profile?->bio ?? ''));
        if ($bio === '') {
            $bio = $defaultBio;
        }

        $profileUrl = ($user && ! empty($user->slug) && Route::has('frontend.authors.show'))
            ? route('frontend.authors.show', $user->slug)
            : null;

        return [
            'name' => $name,
            'bio' => Str::limit(strip_tags($bio), 140),
            'avatar_url' => $avatar['url'] ?? null,
            'initials' => $avatar['initials'] ?? strtoupper(substr($name, 0, 1)),
            'color' => $avatar['color'] ?? '#0f766e',
            'profile_url' => $profileUrl,
            'published_label' => $publishedLabel,
        ];
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  callable(Model): string  $urlResolver
     * @return list<array{name: string, url: string, description: ?string, is_current: bool}>
     */
    protected function relatedCategories(
        ?Model $category,
        ?int $orgId,
        string $modelClass,
        callable $urlResolver,
    ): array {
        if (! $category) {
            return [];
        }

        $picked = collect();

        $children = $modelClass::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->where('parent_id', $category->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(self::CATEGORY_LIMIT)
            ->get(['id', 'name', 'slug', 'description', 'parent_id']);

        $picked = $picked->merge($children);

        if ($picked->count() < self::CATEGORY_LIMIT) {
            $siblings = $modelClass::query()
                ->when($orgId, fn ($q) => $q->forOrg($orgId))
                ->where('status', 'active')
                ->where('parent_id', $category->parent_id)
                ->where('id', '!=', $category->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(self::CATEGORY_LIMIT - $picked->count())
                ->get(['id', 'name', 'slug', 'description', 'parent_id']);

            $picked = $picked->merge($siblings);
        }

        if ($category->parent_id && $picked->count() < self::CATEGORY_LIMIT) {
            $parent = $modelClass::query()
                ->when($orgId, fn ($q) => $q->forOrg($orgId))
                ->where('status', 'active')
                ->whereKey($category->parent_id)
                ->first(['id', 'name', 'slug', 'description', 'parent_id']);

            if ($parent) {
                $picked = collect([$parent])->merge($picked);
            }
        }

        // Always surface the current category first when space allows.
        $picked = collect([$category])->merge($picked)->unique('id')->take(self::CATEGORY_LIMIT)->values();

        return $picked->map(function (Model $item) use ($category, $urlResolver) {
            return [
                'name' => (string) $item->name,
                'url' => $urlResolver($item),
                'description' => filled($item->description)
                    ? Str::limit(strip_tags((string) $item->description), 72)
                    : null,
                'is_current' => (int) $item->id === (int) $category->id,
            ];
        })->all();
    }

    /**
     * @return list<array{url: string, title: string, image: ?string, kicker: ?string, meta: ?string}>
     */
    protected function latestBlogs(Blog $blog, ?int $orgId): array
    {
        return Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->whereKeyNot($blog->id)
            ->with(['category:id,name,slug', 'bannerImage', 'banners'])
            ->latest('published_at')
            ->limit(self::LATEST_POOL)
            ->get(['id', 'title', 'slug', 'blog_category_id', 'banner_image_id', 'published_at'])
            ->shuffle()
            ->take(self::LATEST_LIMIT)
            ->values()
            ->map(fn (Blog $item) => [
                'url' => route('frontend.blogs.show', $item->slug),
                'title' => $item->title,
                'image' => $item->bannerUrl(),
                'kicker' => $item->category?->name,
                'meta' => $item->published_at?->format('d M Y'),
            ])
            ->all();
    }

    /**
     * @return list<array{url: string, title: string, image: ?string, kicker: ?string, meta: ?string}>
     */
    protected function latestNews(News $news, ?int $orgId): array
    {
        return News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->whereKeyNot($news->id)
            ->with(['category:id,name,slug', 'bannerImage', 'featuredImage', 'banners'])
            ->latest('published_at')
            ->limit(self::LATEST_POOL)
            ->get([
                'id',
                'title',
                'slug',
                'news_category_id',
                'banner_image_id',
                'featured_image_id',
                'published_at',
                'is_breaking',
            ])
            ->shuffle()
            ->take(self::LATEST_LIMIT)
            ->values()
            ->map(function (News $item) {
                $kicker = $item->category?->name ?: 'News';
                if ($item->is_breaking) {
                    $kicker = 'Breaking · '.$kicker;
                }

                return [
                    'url' => route('frontend.news.show', $item->slug),
                    'title' => $item->title,
                    'image' => $item->featuredImageUrl() ?: $item->bannerUrl(),
                    'kicker' => $kicker,
                    'meta' => $item->published_at?->diffForHumans(),
                ];
            })
            ->all();
    }
}
