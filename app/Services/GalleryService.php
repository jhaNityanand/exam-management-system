<?php

namespace App\Services;

use App\Models\Gallery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GalleryService
{
    public function paginate(int $organizationId, array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? config('gallery.per_page_default', 24));
        $allowed = config('gallery.per_page_options', [12, 24, 48, 96]);
        if (! in_array($perPage, $allowed, true)) {
            $perPage = (int) config('gallery.per_page_default', 24);
        }

        $query = Gallery::query()->forOrg($organizationId)->with(['uploader:id,name']);

        $trash = ($filters['trash'] ?? 'active') === 'bin';
        if ($trash) {
            $query->onlyTrashed();
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['kind']) && $filters['kind'] !== 'all') {
            $query->where('kind', $filters['kind']);
        }

        if (! empty($filters['mime'])) {
            $query->where('mime_type', 'like', $filters['mime'].'%');
        }

        if (! empty($filters['folder'])) {
            $query->where('folder', $filters['folder']);
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        $sort = (string) ($filters['sort'] ?? 'newest');
        match ($sort) {
            'oldest' => $query->orderBy('created_at'),
            'name_asc' => $query->orderBy('original_name'),
            'name_desc' => $query->orderByDesc('original_name'),
            'size_asc' => $query->orderBy('file_size'),
            'size_desc' => $query->orderByDesc('file_size'),
            default => $query->orderByDesc('created_at'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    public function stats(int $organizationId): array
    {
        return [
            'total' => Gallery::query()->forOrg($organizationId)->count(),
            'images' => Gallery::query()->forOrg($organizationId)->where('kind', 'image')->count(),
            'videos' => Gallery::query()->forOrg($organizationId)->where('kind', 'video')->count(),
            'documents' => Gallery::query()->forOrg($organizationId)->where('kind', 'document')->count(),
            'bin' => Gallery::query()->forOrg($organizationId)->onlyTrashed()->count(),
            'total_bytes' => (int) Gallery::query()->forOrg($organizationId)->sum('file_size'),
        ];
    }

    /**
     * Store an uploaded file as a single gallery row.
     * Always sets original_file_path; modified stays null until edited.
     *
     * @param  array<string, mixed>  $meta
     */
    public function upload(UploadedFile $file, int $organizationId, array $meta = []): Gallery
    {
        $kind = $this->detectKind($file, $meta['kind'] ?? null);
        $disk = (string) config('gallery.disk', 'public');
        $stored = $this->storeUploadedFile($file, $organizationId, $disk);
        [$width, $height] = $this->readImageDimensions($file, $kind);

        return Gallery::create([
            'organization_id' => $organizationId,
            'original_name' => $meta['original_name'] ?? $file->getClientOriginalName(),
            'file_name' => $stored['file_name'],
            'file_path' => $stored['path'],
            'file_url' => $this->publicUrl($disk, $stored['path']),
            'original_file_path' => $stored['path'],
            'modified_file_path' => null,
            'file_extension' => $stored['extension'],
            'mime_type' => $file->getMimeType(),
            'kind' => $kind,
            'file_size' => $file->getSize() ?: 0,
            'width' => $width,
            'height' => $height,
            'folder' => $meta['folder'] ?? trim((string) config('gallery.directory', 'gallery'), '/'),
            'disk' => $disk,
            'alt_text' => $meta['alt_text'] ?? null,
            'description' => $meta['description'] ?? null,
            'status' => 'active',
            'source' => $meta['source'] ?? 'gallery',
            'module' => $meta['module'] ?? null,
            'thumbnail_path' => $kind === 'image'
                ? $this->createThumbnail($disk, $stored['path'], $organizationId)
                : null,
            'uploaded_by' => $meta['uploaded_by'] ?? Auth::id(),
            'created_by' => $meta['created_by'] ?? Auth::id(),
            'updated_by' => $meta['updated_by'] ?? Auth::id(),
        ]);
    }

    /**
     * Upload raw binary contents (e.g. profile avatar base64) into the gallery.
     *
     * @param  array<string, mixed>  $meta
     */
    public function uploadFromContents(string $contents, string $filename, int $organizationId, array $meta = []): Gallery
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION) ?: 'bin');
        $mime = $meta['mime_type'] ?? match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        $tmp = tempnam(sys_get_temp_dir(), 'gal_');
        if ($tmp === false) {
            throw new \RuntimeException('Unable to create a temporary file for gallery upload.');
        }

        file_put_contents($tmp, $contents);
        $file = new UploadedFile($tmp, $filename, $mime, null, true);

        try {
            return $this->upload($file, $organizationId, $meta);
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * Editor upload: one gallery row.
     * When both original + adjusted files are provided, both paths are stored on that row.
     *
     * @param  array<string, mixed>  $meta
     * @return array{primary: Gallery, original: Gallery, adjusted: ?Gallery}
     */
    public function uploadForEditor(UploadedFile $file, int $organizationId, ?UploadedFile $original = null, array $meta = []): array
    {
        $meta = array_merge($meta, [
            'source' => 'editor',
            'folder' => $meta['folder'] ?? config('gallery.directory', 'gallery'),
        ]);

        $kind = $this->detectKind($file, $meta['kind'] ?? null);
        $isImagePair = $kind === 'image' && $original instanceof UploadedFile && $original->isValid();

        if ($isImagePair) {
            $row = $this->upload($original, $organizationId, array_merge($meta, [
                'kind' => 'image',
                'original_name' => (string) ($meta['display_name']
                    ?? $original->getClientOriginalName()
                    ?? $file->getClientOriginalName()),
            ]));

            $row = $this->saveModifiedFile($row, $file);

            return [
                'primary' => $row,
                'original' => $row,
                'adjusted' => $row,
            ];
        }

        $row = $this->upload($file, $organizationId, $meta);

        return [
            'primary' => $row,
            'original' => $row,
            'adjusted' => null,
        ];
    }

    /**
     * Save (or replace) a modified image for an existing gallery row.
     * Deletes any previous modified file; leaves the original untouched.
     */
    public function saveModifiedFile(Gallery $gallery, UploadedFile $file): Gallery
    {
        $disk = $gallery->disk ?: (string) config('gallery.disk', 'public');

        return DB::transaction(function () use ($gallery, $file, $disk) {
            $oldModified = $gallery->modified_file_path;
            if ($oldModified
                && $oldModified !== $gallery->original_file_path
                && Storage::disk($disk)->exists($oldModified)
            ) {
                Storage::disk($disk)->delete($oldModified);
            }

            $stored = $this->storeUploadedFile(
                $file,
                (int) $gallery->organization_id,
                $disk,
                'edited'
            );
            [$width, $height] = $this->readImageDimensions($file, 'image');

            $gallery->forceFill([
                'modified_file_path' => $stored['path'],
                'file_path' => $stored['path'],
                'file_url' => $this->publicUrl($disk, $stored['path']),
                'file_name' => $stored['file_name'],
                'file_extension' => $stored['extension'],
                'mime_type' => $file->getMimeType() ?: $gallery->mime_type,
                'file_size' => $file->getSize() ?: 0,
                'width' => $width,
                'height' => $height,
                'kind' => 'image',
                'updated_by' => Auth::id(),
            ])->save();

            return $gallery->fresh(['uploader']);
        });
    }

    /**
     * Clear the modified file and revert display to the original.
     */
    public function revertToOriginal(Gallery $gallery): Gallery
    {
        $disk = $gallery->disk ?: (string) config('gallery.disk', 'public');
        $originalPath = $gallery->original_file_path;

        if (! $originalPath || ! Storage::disk($disk)->exists($originalPath)) {
            throw new \RuntimeException('Original file is missing on disk.');
        }

        return DB::transaction(function () use ($gallery, $disk, $originalPath) {
            $oldModified = $gallery->modified_file_path;
            if ($oldModified
                && $oldModified !== $originalPath
                && Storage::disk($disk)->exists($oldModified)
            ) {
                Storage::disk($disk)->delete($oldModified);
            }

            [$width, $height] = [null, null];
            try {
                $full = Storage::disk($disk)->path($originalPath);
                $size = @getimagesize($full);
                if (is_array($size)) {
                    $width = (int) ($size[0] ?? 0) ?: null;
                    $height = (int) ($size[1] ?? 0) ?: null;
                }
            } catch (\Throwable) {
                // ignore
            }

            $gallery->forceFill([
                'modified_file_path' => null,
                'file_path' => $originalPath,
                'file_url' => $this->publicUrl($disk, $originalPath),
                'file_name' => basename($originalPath),
                'file_extension' => strtolower(pathinfo($originalPath, PATHINFO_EXTENSION) ?: $gallery->file_extension),
                'file_size' => Storage::disk($disk)->size($originalPath) ?: $gallery->file_size,
                'width' => $width,
                'height' => $height,
                'updated_by' => Auth::id(),
            ])->save();

            return $gallery->fresh(['uploader']);
        });
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return list<Gallery>
     */
    public function uploadMany(array $files, int $organizationId, array $meta = []): array
    {
        $created = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $created[] = $this->upload($file, $organizationId, $meta);
            }
        }

        return $created;
    }

    public function rename(Gallery $gallery, string $newName): Gallery
    {
        $newName = trim($newName);
        if ($newName === '') {
            throw new \InvalidArgumentException('File name cannot be empty.');
        }

        // Keep extension if user omitted it.
        $extension = $gallery->file_extension;
        if ($extension && ! str_ends_with(strtolower($newName), '.'.strtolower($extension))) {
            $newName .= '.'.$extension;
        }

        $gallery->update([
            'original_name' => $newName,
            'alt_text' => $gallery->alt_text ?: pathinfo($newName, PATHINFO_FILENAME),
        ]);

        return $gallery->fresh(['uploader']);
    }

    public function updateMeta(Gallery $gallery, array $data): Gallery
    {
        $gallery->update(array_filter([
            'alt_text' => $data['alt_text'] ?? $gallery->alt_text,
            'description' => $data['description'] ?? $gallery->description,
            'status' => $data['status'] ?? $gallery->status,
        ], static fn ($v) => $v !== null));

        return $gallery->fresh(['uploader']);
    }

    public function softDelete(Gallery $gallery): Gallery
    {
        if ($gallery->trashed()) {
            return $gallery;
        }

        return DB::transaction(function () use ($gallery) {
            $disk = $gallery->disk ?: (string) config('gallery.disk', 'public');
            $displayPath = $gallery->displayPath();

            $uniquePaths = array_values(array_unique(array_filter([
                $gallery->original_file_path,
                $gallery->modified_file_path,
                $displayPath,
            ])));

            $moved = [];
            foreach ($uniquePaths as $path) {
                $moved[$path] = $this->movePathToBin($disk, $gallery, $path);
            }

            $newOriginal = $gallery->original_file_path
                ? ($moved[$gallery->original_file_path] ?? $gallery->original_file_path)
                : null;
            $newModified = $gallery->modified_file_path
                ? ($moved[$gallery->modified_file_path] ?? $gallery->modified_file_path)
                : null;
            $newDisplay = $displayPath
                ? ($moved[$displayPath] ?? ($newModified ?: $newOriginal))
                : ($newModified ?: $newOriginal);

            $gallery->forceFill([
                'original_file_path' => $newOriginal,
                'modified_file_path' => $newModified,
                'original_path' => $displayPath ?: $gallery->original_path,
                'bin_path' => $newDisplay,
                'file_path' => $newDisplay ?: $gallery->file_path,
                'file_url' => $newDisplay
                    ? $this->publicUrl($disk, $newDisplay)
                    : $gallery->file_url,
                'updated_by' => Auth::id(),
            ])->save();

            $gallery->delete();

            return $gallery->fresh();
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function softDeleteMany(int $organizationId, array $ids): int
    {
        $items = Gallery::query()->forOrg($organizationId)->whereIn('id', $ids)->get();
        $count = 0;
        foreach ($items as $item) {
            $this->softDelete($item);
            $count++;
        }

        return $count;
    }

    public function restore(Gallery $gallery): Gallery
    {
        if (! $gallery->trashed()) {
            return $gallery;
        }

        return DB::transaction(function () use ($gallery) {
            $disk = $gallery->disk ?: (string) config('gallery.disk', 'public');

            $restoredOriginal = $this->restorePathFromBin($disk, $gallery, $gallery->original_file_path);
            $restoredModified = $this->restorePathFromBin($disk, $gallery, $gallery->modified_file_path);

            $displayRestore = $gallery->original_path;
            $displaySource = $gallery->bin_path ?: $gallery->file_path;

            // Prefer reconstructed original/modified; fall back to legacy single-path restore.
            $newDisplay = $restoredModified ?: $restoredOriginal;
            if (! $newDisplay && $displaySource) {
                $target = $displayRestore ?: $this->defaultGalleryPath($gallery, basename((string) $displaySource));
                $newDisplay = $this->relocateDiskPath($disk, (string) $displaySource, $target);
            }

            $gallery->restore();

            $gallery->forceFill([
                'original_file_path' => $restoredOriginal ?: $gallery->original_file_path,
                'modified_file_path' => $restoredModified,
                'file_path' => $newDisplay ?: $gallery->file_path,
                'file_url' => $this->publicUrl($disk, $newDisplay ?: $gallery->file_path),
                'bin_path' => null,
                'original_path' => null,
                'restored_at' => now(),
                'updated_by' => Auth::id(),
            ])->save();

            return $gallery->fresh(['uploader']);
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function restoreMany(int $organizationId, array $ids): int
    {
        $items = Gallery::query()->forOrg($organizationId)->onlyTrashed()->whereIn('id', $ids)->get();
        $count = 0;
        foreach ($items as $item) {
            $this->restore($item);
            $count++;
        }

        return $count;
    }

    public function forceDelete(Gallery $gallery): bool
    {
        return DB::transaction(function () use ($gallery) {
            $disk = $gallery->disk ?: (string) config('gallery.disk', 'public');
            $paths = array_filter([
                $gallery->file_path,
                $gallery->bin_path,
                $gallery->original_path,
                $gallery->original_file_path,
                $gallery->modified_file_path,
                $gallery->thumbnail_path,
            ]);

            foreach (array_unique($paths) as $path) {
                if ($path && Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }
            }

            return (bool) $gallery->forceDelete();
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function forceDeleteMany(int $organizationId, array $ids): int
    {
        $items = Gallery::query()->forOrg($organizationId)->withTrashed()->whereIn('id', $ids)->get();
        $count = 0;
        foreach ($items as $item) {
            $this->forceDelete($item);
            $count++;
        }

        return $count;
    }

    public function toArray(Gallery $gallery): array
    {
        $disk = $gallery->disk ?: 'public';
        $displayPath = $gallery->displayPath();
        $originalPath = $gallery->original_file_path;
        $modifiedPath = $gallery->hasModification() ? $gallery->modified_file_path : null;

        return [
            'id' => $gallery->id,
            'original_name' => $gallery->original_name,
            'file_name' => $gallery->file_name,
            'file_path' => $displayPath,
            'file_url' => $this->publicUrl($disk, $displayPath),
            'original_file_path' => $originalPath,
            'modified_file_path' => $modifiedPath,
            'original_url' => $originalPath ? $this->publicUrl($disk, $originalPath) : null,
            'modified_url' => $modifiedPath ? $this->publicUrl($disk, $modifiedPath) : null,
            'has_modification' => $gallery->hasModification(),
            'file_extension' => $gallery->file_extension,
            'mime_type' => $gallery->mime_type,
            'kind' => $gallery->kind,
            'file_size' => $gallery->file_size,
            'human_size' => $gallery->humanSize(),
            'width' => $gallery->width,
            'height' => $gallery->height,
            'dimensions' => $gallery->dimensionsLabel(),
            'folder' => $gallery->folder,
            'alt_text' => $gallery->alt_text,
            'description' => $gallery->description,
            'status' => $gallery->status,
            'source' => $gallery->source,
            'module' => $gallery->module,
            'thumbnail_path' => $gallery->thumbnail_path,
            'thumbnail_url' => $gallery->thumbnail_path
                ? $this->publicUrl($disk, $gallery->thumbnail_path)
                : null,
            'uploaded_by' => $gallery->uploaded_by,
            'uploader_name' => $gallery->uploader?->name,
            'created_at' => optional($gallery->created_at)?->toIso8601String(),
            'created_at_human' => optional($gallery->created_at)?->diffForHumans(),
            'created_at_formatted' => optional($gallery->created_at)?->format('M j, Y g:i A'),
            'deleted_at' => optional($gallery->deleted_at)?->toIso8601String(),
            'is_trashed' => $gallery->trashed(),
            'is_image' => $gallery->isImage(),
        ];
    }

    /**
     * TinyMCE / editor JSON shape (single-row compatible).
     *
     * @param  array{primary: Gallery, original: Gallery, adjusted: ?Gallery}  $pair
     * @return array<string, mixed>
     */
    public function editorUploadResponse(array $pair): array
    {
        $primary = $pair['primary'];
        $location = $this->freshPublicUrl($primary);
        $disk = $primary->disk ?: 'public';
        $originalPath = $primary->original_file_path ?: $primary->file_path;
        $modifiedPath = $primary->hasModification() ? $primary->modified_file_path : null;

        return [
            'location' => $location,
            'url' => $location,
            'id' => $primary->id,
            'kind' => $primary->kind,
            'name' => $primary->original_name,
            'mime' => $primary->mime_type,
            'size' => $primary->file_size,
            'width' => $primary->width,
            'height' => $primary->height,
            'original' => [
                'id' => $primary->id,
                'url' => $this->publicUrl($disk, (string) $originalPath),
                'path' => $originalPath,
                'size' => $primary->file_size,
                'width' => $primary->width,
                'height' => $primary->height,
            ],
            'adjusted' => $modifiedPath ? [
                'id' => $primary->id,
                'url' => $this->publicUrl($disk, $modifiedPath),
                'path' => $modifiedPath,
                'size' => $primary->file_size,
                'width' => $primary->width,
                'height' => $primary->height,
            ] : null,
        ];
    }

    /**
     * Extract public storage URLs that belong to gallery media.
     *
     * @return list<string>
     */
    public function extractUrlsFromHtml(?string $html): array
    {
        if (! is_string($html) || $html === '') {
            return [];
        }

        $urls = [];
        $patterns = [
            '/src=["\']([^"\']+)["\']/i',
            '/href=["\']([^"\']+)["\']/i',
            '/data=["\']([^"\']+)["\']/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[1] as $url) {
                    $normalized = $this->normalizeUrl($url);
                    if ($normalized && $this->looksLikeGalleryUrl($normalized)) {
                        $urls[] = $normalized;
                    }
                }
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * @param  list<string>|string|null  $htmlParts
     */
    public function syncForModel(Model $model, array|string|null $htmlParts, ?int $organizationId = null): void
    {
        $orgId = $organizationId
            ?? (int) ($model->getAttribute('organization_id') ?? current_organization_id() ?? 0);

        if ($orgId < 1) {
            return;
        }

        $parts = is_array($htmlParts) ? $htmlParts : [$htmlParts];
        $urls = [];
        foreach ($parts as $part) {
            if (is_array($part)) {
                $urls = array_merge($urls, $this->extractUrlsFromValue($part));
            } else {
                $urls = array_merge($urls, $this->extractUrlsFromHtml(is_string($part) ? $part : null));
            }
        }
        $urls = array_values(array_unique($urls));

        $previous = Gallery::query()
            ->where('organization_id', $orgId)
            ->where('attachable_type', $model->getMorphClass())
            ->where('attachable_id', $model->getKey())
            ->get();

        $keepIds = [];

        if ($urls) {
            $matched = Gallery::query()
                ->where('organization_id', $orgId)
                ->where(function ($query) use ($urls) {
                    foreach ($urls as $url) {
                        $path = ltrim((string) (parse_url($url, PHP_URL_PATH) ?: ''), '/');
                        $query->orWhere('file_url', $url)
                            ->orWhere('file_url', 'like', '%'.($path !== '' ? $path : $url).'%')
                            ->orWhere('file_path', $path)
                            ->orWhere('file_path', 'like', '%'.basename($path).'%')
                            ->orWhere('original_file_path', $path)
                            ->orWhere('modified_file_path', $path)
                            ->orWhere('original_file_path', 'like', '%'.basename($path).'%')
                            ->orWhere('modified_file_path', 'like', '%'.basename($path).'%');
                    }
                })
                ->get();

            foreach ($matched as $media) {
                $media->forceFill([
                    'attachable_type' => $model->getMorphClass(),
                    'attachable_id' => $model->getKey(),
                    'last_referenced_at' => now(),
                ])->save();
                $keepIds[] = $media->id;
            }
        }

        foreach ($previous as $media) {
            if (in_array($media->id, $keepIds, true)) {
                continue;
            }

            $media->forceFill([
                'attachable_type' => null,
                'attachable_id' => null,
            ])->save();
        }
    }

    public function purgeForModel(Model $model): void
    {
        $items = Gallery::query()
            ->where('attachable_type', $model->getMorphClass())
            ->where('attachable_id', $model->getKey())
            ->get();

        foreach ($items as $media) {
            try {
                if (! $media->trashed()) {
                    $this->softDelete($media);
                }
            } catch (\Throwable) {
                // Continue detaching even if bin move fails.
            }
        }
    }

    public function pruneOrphans(?int $olderThanHours = null): int
    {
        $hours = $olderThanHours ?? (int) config('editor.orphan_ttl_hours', 24);
        $cutoff = now()->subHours(max(1, $hours));

        $orphans = Gallery::query()
            ->where('source', 'editor')
            ->whereNull('attachable_id')
            ->where('created_at', '<', $cutoff)
            ->get();

        $count = 0;
        foreach ($orphans as $media) {
            try {
                $this->softDelete($media);
                $count++;
            } catch (\Throwable) {
                // skip
            }
        }

        return $count;
    }

    /**
     * Remove Base64 data-URI embeds so HTML never stores raw file payloads.
     */
    public function stripDataUris(?string $html): ?string
    {
        if (! is_string($html) || $html === '') {
            return $html;
        }

        $cleaned = preg_replace(
            '/\s(?:src|href)=([\'"])data:[^\'"]*\1/i',
            '',
            $html
        );

        return is_string($cleaned) ? $cleaned : $html;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    public function sanitizeHtmlFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            if (is_string($data[$field])) {
                $data[$field] = $this->stripDataUris($data[$field]);
            } elseif (is_array($data[$field])) {
                $data[$field] = $this->sanitizeNestedHtml($data[$field]);
            }
        }

        return $data;
    }

    /**
     * @param  array<mixed>  $items
     * @return array<mixed>
     */
    protected function sanitizeNestedHtml(array $items): array
    {
        foreach ($items as $key => $item) {
            if (is_string($item)) {
                $items[$key] = $this->stripDataUris($item);
            } elseif (is_array($item)) {
                $items[$key] = $this->sanitizeNestedHtml($item);
            }
        }

        return $items;
    }

    /**
     * @param  mixed  $value
     * @return list<string>
     */
    protected function extractUrlsFromValue(mixed $value): array
    {
        if (is_string($value)) {
            return $this->extractUrlsFromHtml($value);
        }

        if (! is_array($value)) {
            return [];
        }

        $urls = [];
        foreach ($value as $item) {
            if (is_string($item)) {
                $urls = array_merge($urls, $this->extractUrlsFromHtml($item));
            } elseif (is_array($item)) {
                foreach (['text', 'body', 'html', 'content', 'label'] as $key) {
                    if (! empty($item[$key]) && is_string($item[$key])) {
                        $urls = array_merge($urls, $this->extractUrlsFromHtml($item[$key]));
                    }
                }
            }
        }

        return $urls;
    }

    protected function looksLikeGalleryUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $galleryDirectory = trim((string) config('gallery.directory', 'gallery'), '/');

        return str_contains($path, '/storage/'.$galleryDirectory.'/')
            || str_contains($path, '/'.$galleryDirectory.'/');
    }

    protected function normalizeUrl(string $url): ?string
    {
        $url = html_entity_decode(trim($url));
        if ($url === '' || str_starts_with($url, 'data:') || str_starts_with($url, 'blob:')) {
            return null;
        }

        if (str_starts_with($url, '//')) {
            $url = (request()->isSecure() ? 'https:' : 'http:').$url;
        }

        if (str_starts_with($url, '/')) {
            return url($url);
        }

        return $url;
    }

    public function freshPublicUrl(Gallery $gallery): string
    {
        return $this->publicUrl($gallery->disk ?: 'public', $gallery->displayPath());
    }

    public function publicUrl(string $disk, string $path): string
    {
        $path = str_replace('\\', '/', ltrim((string) $path, '/'));
        $base = rtrim((string) config('app.url', ''), '/');

        if ($path === '') {
            return $base !== '' ? $base.'/' : url('/');
        }

        $prefixes = (array) config('gallery.url_prefixes', [
            'public' => '/storage',
        ]);

        $diskKey = $disk !== '' ? $disk : (string) config('gallery.disk', 'public');
        if (array_key_exists($diskKey, $prefixes)) {
            $prefix = '/'.trim((string) $prefixes[$diskKey], '/');
            $relative = $prefix.'/'.$path;

            // Prefer APP_URL from .env so URLs stay stable across CLI seeding and HTTP.
            if ($base !== '') {
                return $base.$relative;
            }

            return $relative;
        }

        $url = Storage::disk($diskKey)->url($path);
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return ($base !== '' ? $base : '').'/'.ltrim($url, '/');
        }

        return $url;
    }

    /**
     * @return array{path: string, file_name: string, extension: string}
     */
    protected function storeUploadedFile(
        UploadedFile $file,
        int $organizationId,
        string $disk,
        string $suffix = ''
    ): array {
        $directory = trim((string) config('gallery.directory', 'gallery'), '/');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $baseSlug = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
        if ($suffix !== '') {
            $baseSlug .= '_'.$suffix;
        }
        $unique = Str::uuid()->toString();
        $fileName = "{$unique}_{$baseSlug}.{$extension}";
        $relativeDir = sprintf('%s/%d/%s', $directory, $organizationId, now()->format('Y/m'));
        $relativePath = "{$relativeDir}/{$fileName}";

        Storage::disk($disk)->putFileAs($relativeDir, $file, $fileName);

        if (! Storage::disk($disk)->exists($relativePath)) {
            throw new \RuntimeException('Failed to store gallery file on disk.');
        }

        return [
            'path' => $relativePath,
            'file_name' => $fileName,
            'extension' => $extension,
        ];
    }

    protected function movePathToBin(string $disk, Gallery $gallery, ?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (! Storage::disk($disk)->exists($path)) {
            // Already gone — keep the path reference if it looks like a bin path.
            return $path;
        }

        $binRoot = trim((string) config('gallery.bin_directory', 'bin'), '/');
        $binPath = sprintf(
            '%s/%d/%s/%s',
            $binRoot,
            $gallery->organization_id,
            now()->format('Y/m'),
            basename($path)
        );

        if (Storage::disk($disk)->exists($binPath)) {
            $binPath = sprintf(
                '%s/%d/%s/%s_%s',
                $binRoot,
                $gallery->organization_id,
                now()->format('Y/m'),
                Str::uuid()->toString(),
                basename($path)
            );
        }

        return $this->relocateDiskPath($disk, $path, $binPath);
    }

    protected function restorePathFromBin(string $disk, Gallery $gallery, ?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $binRoot = trim((string) config('gallery.bin_directory', 'bin'), '/');
        if (! str_starts_with($path, $binRoot.'/')) {
            // Not in bin — leave as-is if it exists.
            return Storage::disk($disk)->exists($path) ? $path : null;
        }

        $target = $this->defaultGalleryPath($gallery, basename($path));

        return $this->relocateDiskPath($disk, $path, $target);
    }

    protected function defaultGalleryPath(Gallery $gallery, string $basename): string
    {
        $directory = trim((string) config('gallery.directory', 'gallery'), '/');

        return sprintf(
            '%s/%d/%s/%s',
            $directory,
            $gallery->organization_id,
            now()->format('Y/m'),
            $basename
        );
    }

    protected function relocateDiskPath(string $disk, string $from, string $to): string
    {
        if ($from === $to) {
            return $to;
        }

        if (! Storage::disk($disk)->exists($from)) {
            return $to;
        }

        if (Storage::disk($disk)->exists($to)) {
            $to = sprintf('%s/%s_%s', dirname($to), Str::uuid()->toString(), basename($to));
        }

        Storage::disk($disk)->makeDirectory(dirname($to));
        try {
            Storage::disk($disk)->move($from, $to);
        } catch (\Throwable) {
            Storage::disk($disk)->copy($from, $to);
            Storage::disk($disk)->delete($from);
        }

        return $to;
    }

    protected function createThumbnail(string $disk, string $sourcePath, int $organizationId): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        try {
            $absolute = Storage::disk($disk)->path($sourcePath);
            if (! is_file($absolute)) {
                return null;
            }

            $info = @getimagesize($absolute);
            if (! $info || empty($info[0]) || empty($info[1])) {
                return null;
            }

            [$width, $height] = [$info[0], $info[1]];
            $mime = $info['mime'] ?? '';
            $src = match ($mime) {
                'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($absolute),
                'image/png' => @imagecreatefrompng($absolute),
                'image/gif' => @imagecreatefromgif($absolute),
                'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolute) : false,
                default => false,
            };

            if (! $src) {
                return null;
            }

            $maxEdge = 320;
            $scale = min(1, $maxEdge / max($width, $height));
            $tw = max(1, (int) round($width * $scale));
            $th = max(1, (int) round($height * $scale));
            $thumb = imagecreatetruecolor($tw, $th);

            if ($mime === 'image/png' || $mime === 'image/webp') {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
            }

            imagecopyresampled($thumb, $src, 0, 0, 0, 0, $tw, $th, $width, $height);

            $directory = trim((string) config('gallery.directory', 'gallery'), '/');
            $relativeDir = sprintf('%s/%d/%s/thumbs', $directory, $organizationId, now()->format('Y/m'));
            $fileName = pathinfo($sourcePath, PATHINFO_FILENAME).'_thumb.jpg';
            $relativePath = "{$relativeDir}/{$fileName}";
            Storage::disk($disk)->makeDirectory($relativeDir);
            $dest = Storage::disk($disk)->path($relativePath);
            imagejpeg($thumb, $dest, 82);
            imagedestroy($src);
            imagedestroy($thumb);

            return Storage::disk($disk)->exists($relativePath) ? $relativePath : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function detectKind(UploadedFile $file, mixed $preferred = null): string
    {
        if (is_string($preferred) && in_array($preferred, ['image', 'video', 'document', 'file'], true)) {
            return $preferred;
        }

        $mime = (string) $file->getMimeType();
        $ext = strtolower($file->getClientOriginalExtension() ?: '');

        if (str_starts_with($mime, 'image/') || in_array($ext, config('gallery.image_mimes', []), true)) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/') || in_array($ext, config('gallery.video_mimes', []), true)) {
            return 'video';
        }
        if (in_array($ext, config('gallery.document_mimes', []), true)) {
            return 'document';
        }

        return 'file';
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    protected function readImageDimensions(UploadedFile $file, string $kind): array
    {
        if ($kind !== 'image') {
            return [null, null];
        }

        try {
            $size = @getimagesize($file->getRealPath());
            if (is_array($size)) {
                return [(int) ($size[0] ?? 0) ?: null, (int) ($size[1] ?? 0) ?: null];
            }
        } catch (\Throwable) {
            // ignore
        }

        return [null, null];
    }
}
