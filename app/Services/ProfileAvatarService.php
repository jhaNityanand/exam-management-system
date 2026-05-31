<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ProfileAvatarService
{
    public function storeFromBase64(string $dataUrl, int $userId): string
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

        $path = "avatars/{$userId}/".Str::uuid().".{$extension}";
        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
