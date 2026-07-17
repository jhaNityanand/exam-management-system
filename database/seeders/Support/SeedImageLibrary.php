<?php

namespace Database\Seeders\Support;

use App\Models\Gallery;
use App\Services\GalleryService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Downloads thematic images for seeders, stores them in the gallery,
 * and purges previously seeded media so each run regenerates fresh files.
 */
class SeedImageLibrary
{
    public function __construct(
        private readonly GalleryService $gallery = new GalleryService
    ) {}

    /**
     * Remove previously seeded gallery rows and their files for an organization.
     */
    public function purge(int $organizationId, ?string $module = null): int
    {
        $query = Gallery::query()
            ->withTrashed()
            ->where('organization_id', $organizationId)
            ->where(function ($builder) {
                $builder->where('source', 'seeder')
                    ->orWhere('original_name', 'like', 'img-%')
                    ->orWhere('file_name', 'like', 'img-%');
            });

        if ($module !== null) {
            $query->where('module', $module);
        }

        $removed = 0;
        foreach ($query->get() as $item) {
            $this->gallery->forceDelete($item);
            $removed++;
        }

        return $removed;
    }

    /**
     * Download (or synthesize) an image and store it in the gallery with an img- filename prefix.
     *
     * @param  array<string, mixed>  $meta
     */
    public function store(
        int $organizationId,
        string $slug,
        ?int $userId = null,
        string $module = 'content',
        array $meta = []
    ): Gallery {
        $slug = Str::slug($slug) ?: 'seed-image';
        $filename = 'img-'.$slug.'.jpg';
        $width = (int) ($meta['width'] ?? 1200);
        $height = (int) ($meta['height'] ?? 675);
        $contents = $this->downloadBytes($slug, $width, $height);

        return $this->gallery->uploadFromContents($contents, $filename, $organizationId, [
            'source' => 'seeder',
            'module' => $module,
            'kind' => 'image',
            'original_name' => $filename,
            'alt_text' => $meta['alt_text'] ?? Str::headline(str_replace('-', ' ', $slug)),
            'description' => $meta['description'] ?? 'Seeded demo media',
            'uploaded_by' => $userId,
            'created_by' => $userId,
            'updated_by' => $userId,
            'mime_type' => 'image/jpeg',
        ]);
    }

    private function downloadBytes(string $seed, int $width, int $height): string
    {
        $url = sprintf('https://picsum.photos/seed/%s/%d/%d', rawurlencode($seed), $width, $height);

        try {
            $response = Http::timeout(8)
                ->connectTimeout(3)
                ->withHeaders(['Accept' => 'image/*'])
                ->get($url);

            if ($response->successful() && $response->body() !== '') {
                return $response->body();
            }
        } catch (Throwable) {
            // Continue to insecure fallback for local Windows/WAMP CA issues.
        }

        try {
            $response = Http::timeout(8)
                ->connectTimeout(3)
                ->withoutVerifying()
                ->withHeaders(['Accept' => 'image/*'])
                ->get($url);

            if ($response->successful() && $response->body() !== '') {
                return $response->body();
            }
        } catch (Throwable) {
            // Fall through to local generation.
        }

        return $this->synthesizeJpeg($seed, $width, $height);
    }

    private function synthesizeJpeg(string $seed, int $width, int $height): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('Unable to download or generate a seed image (GD extension missing).');
        }

        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new RuntimeException('Unable to allocate seed image canvas.');
        }

        $hash = abs(crc32($seed));
        $r = 40 + ($hash % 140);
        $g = 60 + (($hash >> 3) % 140);
        $b = 90 + (($hash >> 6) % 140);
        $background = imagecolorallocate($image, $r, $g, $b);
        $overlay = imagecolorallocatealpha($image, 255, 255, 255, 90);
        $textColor = imagecolorallocate($image, 255, 255, 255);

        imagefilledrectangle($image, 0, 0, $width, $height, $background);
        imagefilledrectangle($image, 0, (int) ($height * 0.62), $width, $height, $overlay);

        $label = Str::limit(Str::headline(str_replace('-', ' ', $seed)), 42, '');
        imagestring($image, 5, 36, (int) ($height * 0.72), $label !== '' ? $label : 'Seed Image', $textColor);
        imagestring($image, 3, 36, (int) ($height * 0.80), 'img-'.$seed.'.jpg', $textColor);

        ob_start();
        imagejpeg($image, null, 85);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        if ($bytes === '') {
            throw new RuntimeException('Failed to encode generated seed JPEG.');
        }

        return $bytes;
    }
}
