<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\BlogTag;
use App\Support\UniqueOrgSlug;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BlogService
{
    public function __construct(protected GalleryService $gallery) {}

    public function create(array $data, int $orgId): Blog
    {
        $tags = $data['tags'] ?? [];
        $attachmentIds = $data['attachment_ids'] ?? [];
        $bannerIds = $data['banner_ids'] ?? [];
        unset($data['tags'], $data['attachment_ids'], $data['banner_ids']);

        $data['organization_id'] = $orgId;
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? Blog::STATUS_PUBLISHED;

        if (empty($data['author_id'])) {
            $data['author_id'] = Auth::id();
        }
        if (empty($data['author_name'])) {
            $data['author_name'] = Auth::user()?->name;
        }

        $data['slug'] = UniqueOrgSlug::forModel(
            Blog::class,
            $this->slugSource($data['slug'] ?? null, $data['title'] ?? ''),
            $orgId,
        );
        $this->applyPublishedAt($data);
        $data = $this->applyPrimaryBanner($data, is_array($bannerIds) ? $bannerIds : []);

        $data = $this->gallery->sanitizeHtmlFields($data, ['content', 'excerpt']);

        $blog = Blog::create($data);
        $this->syncGalleryMedia($blog);
        $this->syncTags($blog, is_array($tags) ? $tags : [], $orgId);
        $this->syncAttachments($blog, is_array($attachmentIds) ? $attachmentIds : []);
        $this->syncBanners($blog, is_array($bannerIds) ? $bannerIds : []);

        return $blog->fresh(['banners', 'bannerImage', 'tags']);
    }

    public function update(Blog $blog, array $data): Blog
    {
        $tags = $data['tags'] ?? null;
        $attachmentIds = $data['attachment_ids'] ?? null;
        $bannerIds = $data['banner_ids'] ?? null;
        unset($data['tags'], $data['attachment_ids'], $data['banner_ids']);

        if (array_key_exists('slug', $data) || array_key_exists('title', $data)) {
            $data['slug'] = UniqueOrgSlug::forModel(
                Blog::class,
                $this->slugSource($data['slug'] ?? null, $data['title'] ?? $blog->title),
                (int) $blog->organization_id,
                (int) $blog->id,
            );
        }

        if (array_key_exists('status', $data) || array_key_exists('published_at', $data)) {
            $this->applyPublishedAt($data, $blog);
        }

        if ($bannerIds !== null) {
            $data = $this->applyPrimaryBanner($data, is_array($bannerIds) ? $bannerIds : []);
        }

        $data = $this->gallery->sanitizeHtmlFields($data, ['content', 'excerpt']);

        $blog->update($data);
        $blog = $blog->fresh();
        $this->syncGalleryMedia($blog);

        if ($tags !== null) {
            $this->syncTags($blog, is_array($tags) ? $tags : [], (int) $blog->organization_id);
        }
        if ($attachmentIds !== null) {
            $this->syncAttachments($blog, is_array($attachmentIds) ? $attachmentIds : []);
        }
        if ($bannerIds !== null) {
            $this->syncBanners($blog, is_array($bannerIds) ? $bannerIds : []);
        }

        return $blog->fresh(['banners', 'bannerImage', 'tags']);
    }

    public function delete(Blog $blog): bool
    {
        return (bool) $blog->delete();
    }

    public function restore(Blog $blog): Blog
    {
        $blog->restore();

        return $blog->fresh();
    }

    public function bulkDelete(int $orgId, array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return 0;
        }

        return Blog::query()->forOrg($orgId)->whereIn('id', $ids)->delete();
    }

    public function bulkRestore(int $orgId, array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return 0;
        }

        $count = 0;
        $blogs = Blog::query()->forOrg($orgId)->onlyTrashed()->whereIn('id', $ids)->get();
        foreach ($blogs as $blog) {
            $blog->restore();
            $count++;
        }

        return $count;
    }

    public function syncTags(Blog $blog, array $tagNames, int $orgId): void
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

            $tag = BlogTag::query()->firstOrCreate(
                ['organization_id' => $orgId, 'slug' => $slug],
                ['name' => $name]
            );

            $tagIds[] = $tag->id;
        }

        $blog->tags()->sync($tagIds);
    }

    public function syncAttachments(Blog $blog, array $galleryIds): void
    {
        $galleryIds = array_values(array_unique(array_filter(array_map('intval', $galleryIds))));
        $blog->galleryAttachments()->sync($galleryIds);
    }

    /**
     * @param  list<int|string>  $galleryIds
     */
    public function syncBanners(Blog $blog, array $galleryIds): void
    {
        $galleryIds = array_values(array_unique(array_filter(array_map('intval', $galleryIds))));
        $sync = [];
        foreach ($galleryIds as $index => $galleryId) {
            $sync[$galleryId] = ['sort_order' => $index];
        }
        $blog->banners()->sync($sync);
    }

    /**
     * Keep legacy banner_image_id in sync with the first ordered banner.
     *
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

    public function uniqueSlug(string $titleOrSlug, int $orgId, ?int $ignoreId = null): string
    {
        return UniqueOrgSlug::forModel(Blog::class, $titleOrSlug, $orgId, $ignoreId);
    }

    protected function slugSource(mixed $slug, string $fallback): string
    {
        $slug = trim((string) $slug);

        return $slug !== '' ? $slug : $fallback;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function applyPublishedAt(array &$data, ?Blog $existing = null): void
    {
        $status = $data['status'] ?? $existing?->status ?? Blog::STATUS_PUBLISHED;

        if ($status === Blog::STATUS_PUBLISHED) {
            if (empty($data['published_at'])) {
                $data['published_at'] = $existing?->published_at ?? now();
            }
        } elseif (! array_key_exists('published_at', $data) || $data['published_at'] === '' || $data['published_at'] === null) {
            $data['published_at'] = null;
        }
    }

    protected function syncGalleryMedia(Blog $blog): void
    {
        $this->gallery->syncForModel($blog, [
            $blog->content,
            $blog->excerpt,
        ], (int) $blog->organization_id);
    }
}
