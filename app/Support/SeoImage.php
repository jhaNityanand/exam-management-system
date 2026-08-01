<?php

namespace App\Support;

/**
 * Resolve default / fallback social & meta images for frontend SEO.
 */
class SeoImage
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    /**
     * @var array<string, string>
     */
    public const TYPES = [
        'home' => 'home',
        'category' => 'category',
        'exam' => 'exam',
        'question' => 'question',
        'blog' => 'blog',
        'news' => 'news',
        'organization' => 'organization',
        'profile' => 'profile',
        'user' => 'profile',
        'author' => 'profile',
        'contact' => 'contact',
        'about' => 'about',
        'privacy' => 'privacy',
        'terms' => 'terms',
        'default' => 'home',
    ];

    /**
     * Absolute public URL for a typed default image (PNG for social crawlers).
     */
    public static function defaultUrl(string $type = 'home'): string
    {
        $key = self::TYPES[$type] ?? self::TYPES['default'];
        $relativePng = 'frontend/images/seo/'.$key.'.png';

        if (is_file(public_path($relativePng))) {
            return asset($relativePng);
        }

        // Legacy banner fallback
        if (is_file(public_path('frontend/images/banner.svg'))) {
            return asset('frontend/images/banner.svg');
        }

        return asset('frontend/images/logo.svg');
    }

    /**
     * Prefer an uploaded/entity URL; otherwise typed default.
     * Empty strings and null are treated as missing.
     */
    public static function resolve(?string $uploadedUrl, string $type = 'home'): string
    {
        $uploadedUrl = is_string($uploadedUrl) ? trim($uploadedUrl) : '';

        if ($uploadedUrl !== '') {
            // Ensure absolute URL for OG crawlers
            if (str_starts_with($uploadedUrl, 'http://') || str_starts_with($uploadedUrl, 'https://')) {
                return $uploadedUrl;
            }

            return url($uploadedUrl);
        }

        return self::defaultUrl($type);
    }

    /**
     * Map CMS page template / slug to a default image type.
     */
    public static function typeForCmsPage(?string $template, ?string $slug = null): string
    {
        $template = strtolower((string) $template);
        $slug = strtolower((string) $slug);

        return match (true) {
            $template === 'contact' || $slug === 'contact-us' => 'contact',
            $template === 'about' || $slug === 'about-us' => 'about',
            $template === 'privacy' || $slug === 'privacy-policy' => 'privacy',
            $template === 'terms' || $slug === 'terms-and-conditions' => 'terms',
            default => 'organization',
        };
    }
}
