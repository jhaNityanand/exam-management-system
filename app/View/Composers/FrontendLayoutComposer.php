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
            ],
        ]);
    }
}
