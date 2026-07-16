<?php

namespace App\Services;

use App\Models\Gallery;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Profile avatars are stored through GalleryService so they appear in the Gallery module.
 * Profile.avatar continues to store the gallery file_path for backward compatibility.
 */
class ProfileAvatarService
{
    public function __construct(private GalleryService $galleryService) {}

    public function storeFromBase64(string $dataUrl, int $userId, ?int $organizationId = null): string
    {
        if (! preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $matches)) {
            throw new InvalidArgumentException('Invalid avatar image data.');
        }

        $extension = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
        $allowed = ['jpg', 'png', 'gif', 'webp'];

        if (! in_array($extension, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported avatar image type.');
        }

        $encoded = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $contents = base64_decode($encoded, true);

        if ($contents === false) {
            throw new InvalidArgumentException('Could not decode avatar image.');
        }

        if (strlen($contents) > 2 * 1024 * 1024) {
            throw new InvalidArgumentException('Avatar image must be smaller than 2MB.');
        }

        $orgId = $organizationId ?? current_organization_id();
        if ($orgId === null) {
            throw new InvalidArgumentException('No organization found for avatar upload.');
        }

        $mime = match ($extension) {
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        $gallery = $this->galleryService->uploadFromContents(
            $contents,
            "avatar-{$userId}.{$extension}",
            (int) $orgId,
            [
                'source' => 'profile',
                'module' => 'profile',
                'kind' => 'image',
                'original_name' => "avatar.{$extension}",
                'mime_type' => $mime,
                'alt_text' => 'Profile avatar',
            ]
        );

        return $gallery->file_path;
    }

    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        $gallery = Gallery::query()
            ->where('file_path', $path)
            ->orWhere('original_file_path', $path)
            ->orWhere('modified_file_path', $path)
            ->first();

        if ($gallery) {
            $this->galleryService->softDelete($gallery);

            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
