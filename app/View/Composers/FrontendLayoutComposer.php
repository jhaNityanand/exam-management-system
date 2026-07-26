<?php

namespace App\View\Composers;

use App\Services\Frontend\SiteCmsService;
use Illuminate\View\View;

class FrontendLayoutComposer
{
    public function __construct(protected SiteCmsService $cms) {}

    public function compose(View $view): void
    {
        $settings = $this->cms->settings();
        $logoGalleryId = (int) ($settings['brand.logo_gallery_id'] ?? 0);
        $faviconGalleryId = (int) ($settings['brand.favicon_gallery_id'] ?? 0);
        $ogGalleryId = (int) ($settings['brand.og_image_gallery_id'] ?? 0);

        $logoUrl = $logoGalleryId > 0
            ? \App\Models\Gallery::query()->find($logoGalleryId)?->file_url
            : null;
        $faviconUrl = $faviconGalleryId > 0
            ? \App\Models\Gallery::query()->find($faviconGalleryId)?->file_url
            : null;
        $ogImageUrl = $ogGalleryId > 0
            ? \App\Models\Gallery::query()->find($ogGalleryId)?->file_url
            : ($settings['seo.og_image'] ?? null);

        $view->with([
            'headerMenu' => $this->cms->menuItems('header'),
            'footerMenu' => $this->cms->menuItems('footer'),
            'footerLegalMenu' => $this->cms->menuItems('footer_legal'),
            'mobileMenu' => $this->cms->menuItems('mobile')->isNotEmpty()
                ? $this->cms->menuItems('mobile')
                : $this->cms->menuItems('header'),
            'socialLinks' => $this->cms->socialLinks(),
            'siteSettings' => $settings,
            'announcements' => $this->cms->announcements(),
            'siteBrand' => [
                'name' => $settings['brand.site_name'] ?? $settings['site_name'] ?? 'Examtube.in',
                'logo_text' => $settings['brand.logo_text'] ?? $settings['logo_text'] ?? 'Examtube',
                'tagline' => $settings['brand.tagline'] ?? $settings['tagline'] ?? '',
                'description' => $settings['brand.description'] ?? '',
                'logo_url' => $logoUrl,
                'favicon_url' => $faviconUrl,
                'og_image_url' => $ogImageUrl,
            ],
        ]);
    }
}
