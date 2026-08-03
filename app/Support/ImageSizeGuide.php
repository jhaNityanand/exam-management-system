<?php

namespace App\Support;

/**
 * Canonical recommended image sizes used across admin uploads and gallery guidance.
 */
class ImageSizeGuide
{
    /**
     * @return array<string, array{label: string, width: int, height: int, hint: string, group: string}>
     */
    public static function all(): array
    {
        $sizes = [
            'logo' => [
                'label' => 'Site logo',
                'width' => 400,
                'height' => 120,
                'hint' => 'Recommended size: 400 × 120 px (transparent PNG/WebP).',
                'group' => 'Branding',
            ],
            'favicon' => [
                'label' => 'Favicon',
                'width' => 512,
                'height' => 512,
                'hint' => 'Recommended size: 512 × 512 px (square PNG).',
                'group' => 'Branding',
            ],
            'og' => [
                'label' => 'Open Graph / social share',
                'width' => 1200,
                'height' => 630,
                'hint' => 'Recommended size: 1200 × 630 px for social sharing previews.',
                'group' => 'SEO & social',
            ],
            'content_banner' => [
                'label' => 'Blog / news banner',
                'width' => 1600,
                'height' => 900,
                'hint' => 'Recommended size: 1600 × 900 px (16:9). First banner is featured on cards and detail pages.',
                'group' => 'Content',
            ],
            'news_featured' => [
                'label' => 'News featured image',
                'width' => 1200,
                'height' => 675,
                'hint' => 'Recommended size: 1200 × 675 px (16:9) for news cards and listings.',
                'group' => 'Content',
            ],
            'content_attachment' => [
                'label' => 'Content attachment image',
                'width' => 1200,
                'height' => 800,
                'hint' => 'Recommended size: 1200 × 800 px for inline/attachment images.',
                'group' => 'Content',
            ],
            'hero_desktop' => [
                'label' => 'Hero banner (desktop)',
                'width' => 1920,
                'height' => 800,
                'hint' => 'Recommended size: 1920 × 800 px for desktop hero slides.',
                'group' => 'CMS / homepage',
            ],
            'hero_mobile' => [
                'label' => 'Hero banner (mobile)',
                'width' => 1080,
                'height' => 1350,
                'hint' => 'Recommended size: 1080 × 1350 px for mobile hero slides.',
                'group' => 'CMS / homepage',
            ],
            'avatar' => [
                'label' => 'Profile / candidate avatar',
                'width' => 400,
                'height' => 400,
                'hint' => 'Recommended size: 400 × 400 px (square JPG/PNG/WebP).',
                'group' => 'Profiles',
            ],
            'exam_og' => [
                'label' => 'Exam / category social image',
                'width' => 1200,
                'height' => 630,
                'hint' => 'Recommended size: 1200 × 630 px for exam and category share cards.',
                'group' => 'SEO & social',
            ],
        ];

        foreach (AdvertisementCatalog::bannerSizes() as $key => $size) {
            $sizes['ad_'.$key] = [
                'label' => 'Ad — '.$size['label'],
                'width' => (int) $size['width'],
                'height' => (int) $size['height'],
                'hint' => sprintf(
                    'Recommended size: %d × %d px (%s). %s',
                    $size['width'],
                    $size['height'],
                    $size['label'],
                    $size['note']
                ),
                'group' => 'Advertisements',
            ];
        }

        return $sizes;
    }

    /**
     * @return array{label: string, width: int, height: int, hint: string, group: string}|null
     */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    public static function hint(string $key): string
    {
        return self::get($key)['hint'] ?? 'Upload a clear image at the recommended size for this field.';
    }

    public static function sizeLabel(string $key): string
    {
        $size = self::get($key);
        if (! $size) {
            return '';
        }

        return $size['width'].' × '.$size['height'].' px';
    }

    /**
     * Grouped list for the Gallery library upload guidance panel.
     *
     * @return array<string, list<array{label: string, width: int, height: int, hint: string, group: string}>>
     */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (self::all() as $row) {
            $grouped[$row['group']][] = $row;
        }

        return $grouped;
    }

    /**
     * Whether stored dimensions match a recommended size (exact or within 2%).
     */
    public static function matches(?int $width, ?int $height, int $recommendWidth, int $recommendHeight): bool
    {
        if (! $width || ! $height || $recommendWidth < 1 || $recommendHeight < 1) {
            return false;
        }

        if ($width === $recommendWidth && $height === $recommendHeight) {
            return true;
        }

        $wTol = max(2, (int) round($recommendWidth * 0.02));
        $hTol = max(2, (int) round($recommendHeight * 0.02));

        return abs($width - $recommendWidth) <= $wTol
            && abs($height - $recommendHeight) <= $hTol;
    }
}
