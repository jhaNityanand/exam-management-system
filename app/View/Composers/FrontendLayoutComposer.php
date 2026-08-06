<?php

namespace App\View\Composers;

use App\Models\Gallery;
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

        $galleryIds = array_values(array_unique(array_filter([
            $logoGalleryId,
            $faviconGalleryId,
            $ogGalleryId,
        ])));

        $galleries = $galleryIds === []
            ? collect()
            : Gallery::query()->whereIn('id', $galleryIds)->get()->keyBy('id');

        $logoUrl = $logoGalleryId > 0 ? ($galleries->get($logoGalleryId)?->file_url) : null;
        $faviconUrl = $faviconGalleryId > 0 ? ($galleries->get($faviconGalleryId)?->file_url) : null;
        $ogImageUrl = $ogGalleryId > 0
            ? ($galleries->get($ogGalleryId)?->file_url)
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
            'frontendAdPageKey' => \App\Support\AdvertisementCatalog::pageKeyFromRoute(
                optional(request()->route())->getName()
            ),
            'adsPreviewMode' => function_exists('ads_preview_mode') ? ads_preview_mode() : false,
            'siteBrand' => [
                'name' => $settings['brand.site_name'] ?? $settings['site_name'] ?? 'Examtube.in',
                'logo_text' => $settings['brand.logo_text'] ?? $settings['logo_text'] ?? 'Examtube',
                'tagline' => $settings['brand.tagline'] ?? $settings['tagline'] ?? '',
                'description' => $settings['brand.description'] ?? '',
                'application_url' => $settings['brand.application_url'] ?? '',
                'application_host' => \App\Services\Settings\OrganizationSettingsService::applicationHost(
                    $settings['brand.application_url'] ?? null
                ),
                'logo_url' => $logoUrl,
                'favicon_url' => $faviconUrl,
                'og_image_url' => $ogImageUrl,
            ],
        ]);
    }
}
