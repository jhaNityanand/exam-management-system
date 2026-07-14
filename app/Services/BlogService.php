<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\BlogTag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BlogService
{
    public function __construct(protected GalleryService $gallery) {}

    public function create(array $data, int $orgId): Blog
    {
        $tags = $data['tags'] ?? [];
        $attachmentIds = $data['attachment_ids'] ?? [];
        unset($data['tags'], $data['attachment_ids']);

        $data['organization_id'] = $orgId;
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? Blog::STATUS_PUBLISHED;

        if (empty($data['author_id'])) {
            $data['author_id'] = Auth::id();
        }
        if (empty($data['author_name'])) {
            $data['author_name'] = Auth::user()?->name;
        }

        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['title'] ?? '', $orgId);
        $this->applyPublishedAt($data);

        $data = $this->gallery->sanitizeHtmlFields($data, ['content', 'excerpt']);

        $blog = Blog::create($data);
        $this->syncGalleryMedia($blog);
        $this->syncTags($blog, is_array($tags) ? $tags : [], $orgId);
        $this->syncAttachments($blog, is_array($attachmentIds) ? $attachmentIds : []);

        return $blog->fresh();
    }

    public function update(Blog $blog, array $data): Blog
    {
        $tags = $data['tags'] ?? null;
        $attachmentIds = $data['attachment_ids'] ?? null;
        unset($data['tags'], $data['attachment_ids']);

        if (array_key_exists('slug', $data) || array_key_exists('title', $data)) {
            $slugSource = $data['slug'] ?? $data['title'] ?? $blog->title;
            $data['slug'] = $this->uniqueSlug($slugSource, (int) $blog->organization_id, (int) $blog->id);
        }

        if (array_key_exists('status', $data) || array_key_exists('published_at', $data)) {
            $this->applyPublishedAt($data, $blog);
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

        return $blog;
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

    public function uniqueSlug(string $titleOrSlug, int $orgId, ?int $ignoreId = null): string
    {
        $base = Str::slug($titleOrSlug);
        if ($base === '') {
            $base = Str::slug(uniqid('blog-', false));
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
        $query = Blog::query()->forOrg($orgId)->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
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
