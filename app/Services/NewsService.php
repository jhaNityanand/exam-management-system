<?php

namespace App\Services;

use App\Models\News;
use App\Models\NewsTag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class NewsService
{
    public function __construct(protected GalleryService $gallery) {}

    public function create(array $data, int $orgId): News
    {
        $tags = $data['tags'] ?? [];
        $attachmentIds = $data['attachment_ids'] ?? [];
        $bannerIds = $data['banner_ids'] ?? [];
        unset($data['tags'], $data['attachment_ids'], $data['banner_ids']);

        $data['organization_id'] = $orgId;
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? News::STATUS_PUBLISHED;
        $data['visibility'] = $data['visibility'] ?? News::VISIBILITY_PUBLIC;
        $data = $this->normalizeFlags($data);

        if (empty($data['author_id'])) {
            $data['author_id'] = Auth::id();
        }
        if (empty($data['author_name'])) {
            $data['author_name'] = Auth::user()?->name;
        }

        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['title'] ?? '', $orgId);
        $this->applyPublishedAt($data);
        $data = $this->applyPrimaryBanner($data, is_array($bannerIds) ? $bannerIds : []);

        $data = $this->gallery->sanitizeHtmlFields($data, ['content', 'excerpt', 'short_description']);

        $news = News::create($data);
        $this->syncGalleryMedia($news);
        $this->syncTags($news, is_array($tags) ? $tags : [], $orgId);
        $this->syncAttachments($news, is_array($attachmentIds) ? $attachmentIds : []);
        $this->syncBanners($news, is_array($bannerIds) ? $bannerIds : []);

        return $news->fresh(['banners', 'bannerImage', 'featuredImage', 'tags']);
    }

    public function update(News $news, array $data): News
    {
        $tags = $data['tags'] ?? null;
        $attachmentIds = $data['attachment_ids'] ?? null;
        $bannerIds = $data['banner_ids'] ?? null;
        unset($data['tags'], $data['attachment_ids'], $data['banner_ids']);

        if (array_key_exists('slug', $data) || array_key_exists('title', $data)) {
            $slugSource = $data['slug'] ?? $data['title'] ?? $news->title;
            $data['slug'] = $this->uniqueSlug($slugSource, (int) $news->organization_id, (int) $news->id);
        }

        if (array_key_exists('status', $data) || array_key_exists('published_at', $data)) {
            $this->applyPublishedAt($data, $news);
        }

        $data = $this->normalizeFlags($data);

        if ($bannerIds !== null) {
            $data = $this->applyPrimaryBanner($data, is_array($bannerIds) ? $bannerIds : []);
        }

        $data = $this->gallery->sanitizeHtmlFields($data, ['content', 'excerpt', 'short_description']);

        $news->update($data);
        $news = $news->fresh();
        $this->syncGalleryMedia($news);

        if ($tags !== null) {
            $this->syncTags($news, is_array($tags) ? $tags : [], (int) $news->organization_id);
        }
        if ($attachmentIds !== null) {
            $this->syncAttachments($news, is_array($attachmentIds) ? $attachmentIds : []);
        }
        if ($bannerIds !== null) {
            $this->syncBanners($news, is_array($bannerIds) ? $bannerIds : []);
        }

        return $news->fresh(['banners', 'bannerImage', 'featuredImage', 'tags']);
    }

    public function delete(News $news): bool
    {
        return (bool) $news->delete();
    }

    public function restore(News $news): News
    {
        $news->restore();

        return $news->fresh();
    }

    public function bulkDelete(int $orgId, array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return 0;
        }

        return News::query()->forOrg($orgId)->whereIn('id', $ids)->delete();
    }

    public function bulkRestore(int $orgId, array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return 0;
        }

        $count = 0;
        $items = News::query()->forOrg($orgId)->onlyTrashed()->whereIn('id', $ids)->get();
        foreach ($items as $news) {
            $news->restore();
            $count++;
        }

        return $count;
    }

    public function syncTags(News $news, array $tagNames, int $orgId): void
    {
        $tagIds = [];

        foreach ($tagNames as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }

            $slug = Str::slug($name);
            if ($slug === '') {
                $slug = Str::slug(uniqid('tag-', false));
            }

            $tag = NewsTag::query()->firstOrCreate(
                ['organization_id' => $orgId, 'slug' => $slug],
                ['name' => $name]
            );

            $tagIds[] = $tag->id;
        }

        $news->tags()->sync($tagIds);
    }

    public function syncAttachments(News $news, array $galleryIds): void
    {
        $galleryIds = array_values(array_unique(array_filter(array_map('intval', $galleryIds))));
        $news->galleryAttachments()->sync($galleryIds);
    }

    /**
     * @param  list<int|string>  $galleryIds
     */
    public function syncBanners(News $news, array $galleryIds): void
    {
        $galleryIds = array_values(array_unique(array_filter(array_map('intval', $galleryIds))));
        $sync = [];
        foreach ($galleryIds as $index => $galleryId) {
            $sync[$galleryId] = ['sort_order' => $index];
        }
        $news->banners()->sync($sync);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int|string>  $bannerIds
     * @return array<string, mixed>
     */
    protected function applyPrimaryBanner(array $data, array $bannerIds): array
    {
        $bannerIds = array_values(array_unique(array_filter(array_map('intval', $bannerIds))));
        $data['banner_image_id'] = $bannerIds[0] ?? ($data['banner_image_id'] ?? null);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeFlags(array $data): array
    {
        foreach (['is_featured', 'is_breaking', 'is_trending'] as $flag) {
            if (array_key_exists($flag, $data)) {
                $data[$flag] = filter_var($data[$flag], FILTER_VALIDATE_BOOLEAN);
            }
        }

        if (array_key_exists('sort_order', $data)) {
            $data['sort_order'] = max(0, (int) $data['sort_order']);
        }

        return $data;
    }

    public function uniqueSlug(string $titleOrSlug, int $orgId, ?int $ignoreId = null): string
    {
        $base = Str::slug($titleOrSlug);
        if ($base === '') {
            $base = Str::slug(uniqid('news-', false));
        }

        $slug = $base;
        $suffix = 2;

        while ($this->slugExists($slug, $orgId, $ignoreId)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    protected function slugExists(string $slug, int $orgId, ?int $ignoreId = null): bool
    {
        $query = News::query()->forOrg($orgId)->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function applyPublishedAt(array &$data, ?News $existing = null): void
    {
        $status = $data['status'] ?? $existing?->status ?? News::STATUS_PUBLISHED;

        if ($status === News::STATUS_PUBLISHED) {
            if (empty($data['published_at'])) {
                $data['published_at'] = $existing?->published_at ?? now();
            }
        } elseif (! array_key_exists('published_at', $data) || $data['published_at'] === '' || $data['published_at'] === null) {
            $data['published_at'] = null;
        }
    }

    protected function syncGalleryMedia(News $news): void
    {
        $this->gallery->syncForModel($news, [
            $news->content,
            $news->excerpt,
            $news->short_description,
        ], (int) $news->organization_id);

        $galleryIds = array_values(array_unique(array_filter([
            $news->banner_image_id,
            $news->featured_image_id,
            $news->og_image_id,
            ...$news->banners()->pluck('galleries.id')->all(),
            ...$news->galleryAttachments()->pluck('galleries.id')->all(),
        ])));

        if ($galleryIds === []) {
            return;
        }

        \App\Models\Gallery::query()
            ->where('organization_id', $news->organization_id)
            ->whereIn('id', $galleryIds)
            ->update([
                'module' => 'news',
                'attachable_type' => $news->getMorphClass(),
                'attachable_id' => $news->getKey(),
                'last_referenced_at' => now(),
            ]);
    }
}
