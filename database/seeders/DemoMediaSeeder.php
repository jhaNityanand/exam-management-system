<?php

namespace Database\Seeders;

use App\Models\Cms\Advertisement;
use App\Models\Cms\HeroBanner;
use App\Models\Cms\Partner;
use App\Models\Cms\SitePage;
use App\Models\Cms\SiteSetting;
use App\Models\Cms\Testimonial;
use App\Models\Exam;
use App\Models\Gallery;
use App\Models\Organization;
use App\Support\AdvertisementCatalog;
use Database\Seeders\Concerns\ResolvesDemoContext;
use Database\Seeders\Support\SeedImageLibrary;
use Illuminate\Database\Seeder;
use Throwable;

/**
 * Attaches branding, hero, exam, page, partner, testimonial,
 * advertisement, and gallery media using frontend SEO default images.
 */
class DemoMediaSeeder extends Seeder
{
    use ResolvesDemoContext;

    public function run(): void
    {
        $org = $this->demoOrganization();
        $editor = $this->demoEditor();

        if (! $org || ! $editor) {
            $this->command?->warn('DemoMediaSeeder: demo-org or editor missing. Skipping.');

            return;
        }

        $images = new SeedImageLibrary;
        $purged = $images->purge($org->id, 'demo-media');
        $this->command?->info("DemoMediaSeeder: purged {$purged} previously seeded demo-media file(s).");

        $brand = $this->seedBrand($org, $editor->id, $images);
        $this->seedHeroes($org->id, $editor->id, $images);
        $this->seedPartners($org->id, $editor->id, $images);
        $this->seedTestimonials($org->id, $editor->id, $images);
        $this->seedExamBanners($org->id, $editor->id, $images);
        $this->seedPageBanners($org->id, $editor->id, $images);
        $this->seedAdvertisements($org->id, $editor->id, $images);
        $this->seedStandaloneGallery($org->id, $editor->id, $images);
        $this->syncOrganizationColumns($org, $brand['logo'] ?? null, $brand['og'] ?? null);

        app(\App\Services\Advertisement\AdvertisementService::class)->forgetCache($org->id);

        $this->command?->info('DemoMediaSeeder: branding, heroes, partners, testimonials, exams, pages, ads, and gallery attached.');
    }

    /**
     * @return array{logo:?Gallery,favicon:?Gallery,og:?Gallery}
     */
    private function seedBrand(Organization $org, int $userId, SeedImageLibrary $images): array
    {
        $logo = $this->safeSeo($images, $org->id, 'organization', $userId, 'Examtube logo');
        $favicon = $this->safeSeo($images, $org->id, 'home', $userId, 'Examtube favicon');
        $og = $this->safeSeo($images, $org->id, 'home', $userId, 'Default Open Graph image');

        $this->upsertSetting($org->id, 'brand', 'logo_gallery_id', $logo?->id, 'integer', 'Logo gallery ID');
        $this->upsertSetting($org->id, 'brand', 'favicon_gallery_id', $favicon?->id, 'integer', 'Favicon gallery ID');
        $this->upsertSetting($org->id, 'brand', 'og_image_gallery_id', $og?->id, 'integer', 'OG image gallery ID');
        $this->upsertSetting($org->id, 'seo', 'og_image', $og?->file_url, 'string', 'Default OG image URL');

        return compact('logo', 'favicon', 'og');
    }

    private function seedHeroes(int $orgId, int $userId, SeedImageLibrary $images): void
    {
        $slides = [
            [
                'title' => 'Master every competitive exam with confidence',
                'subtitle' => 'Mock tests · Timed practice · Instant insights',
                'description' => 'Examtube.in brings structured exams, expert blogs, and exam news together so you prepare with clarity — not chaos.',
                'badge_text' => 'Trusted by aspirants across India',
                'primary_cta_label' => 'Explore exams',
                'primary_cta_url' => '/exams',
                'secondary_cta_label' => 'Read prep blogs',
                'secondary_cta_url' => '/blogs',
                'show_search' => true,
                'seo' => 'home',
            ],
            [
                'title' => 'Practice like the real exam floor',
                'subtitle' => 'Timers · Negative marking · Detailed analytics',
                'description' => 'Simulate real paper conditions with configurable timers, shuffle rules, and performance tracking designed for serious preparation.',
                'badge_text' => 'Exam-day ready',
                'primary_cta_label' => 'Start a mock test',
                'primary_cta_url' => '/exams',
                'secondary_cta_label' => 'How it works',
                'secondary_cta_url' => '/about-us',
                'show_search' => true,
                'seo' => 'exam',
            ],
            [
                'title' => 'Stay informed with blogs & campus news',
                'subtitle' => 'Strategy · Alerts · Opportunities',
                'description' => 'Follow trending education news and practical study blogs from mentors who understand board exams, university tests, and government recruitment.',
                'badge_text' => 'Updated daily',
                'primary_cta_label' => 'Open newsroom',
                'primary_cta_url' => '/news',
                'secondary_cta_label' => 'Browse blogs',
                'secondary_cta_url' => '/blogs',
                'show_search' => false,
                'seo' => 'news',
            ],
        ];

        HeroBanner::query()->where('organization_id', $orgId)->delete();

        foreach ($slides as $index => $slide) {
            $desktop = $this->safeSeo($images, $orgId, $slide['seo'], $userId, $slide['title'], 'hero-'.$index);
            $mobile = $desktop;

            HeroBanner::query()->create([
                'organization_id' => $orgId,
                'title' => $slide['title'],
                'subtitle' => $slide['subtitle'],
                'description' => $slide['description'],
                'badge_text' => $slide['badge_text'],
                'primary_cta_label' => $slide['primary_cta_label'],
                'primary_cta_url' => $slide['primary_cta_url'],
                'secondary_cta_label' => $slide['secondary_cta_label'],
                'secondary_cta_url' => $slide['secondary_cta_url'],
                'image_id' => $desktop?->id,
                'mobile_image_id' => $mobile?->id,
                'theme' => 'emerald',
                'show_search' => $slide['show_search'],
                'sort_order' => $index + 1,
                'status' => 'active',
            ]);
        }
    }

    private function seedPartners(int $orgId, int $userId, SeedImageLibrary $images): void
    {
        foreach (Partner::query()->where('organization_id', $orgId)->get() as $index => $partner) {
            $logo = $this->safeSeo($images, $orgId, 'organization', $userId, $partner->name.' logo', 'partner-'.$index);
            if ($logo) {
                $partner->update(['logo_id' => $logo->id]);
            }
        }
    }

    private function seedTestimonials(int $orgId, int $userId, SeedImageLibrary $images): void
    {
        foreach (Testimonial::query()->where('organization_id', $orgId)->get() as $index => $row) {
            $avatar = $this->safeSeo($images, $orgId, 'profile', $userId, $row->name.' avatar', 'testimonial-'.$index);
            if ($avatar) {
                $row->update(['avatar_id' => $avatar->id]);
            }
        }
    }

    private function seedExamBanners(int $orgId, int $userId, SeedImageLibrary $images): void
    {
        $exams = Exam::query()
            ->where('organization_id', $orgId)
            ->orderBy('id')
            ->get();

        foreach ($exams as $index => $exam) {
            $banner = $this->safeSeo($images, $orgId, 'exam', $userId, $exam->title.' banner', 'exam-'.$index);
            if (! $banner) {
                continue;
            }

            $exam->update([
                'banner_image_id' => $banner->id,
                'og_image_id' => $banner->id,
            ]);
        }
    }

    private function seedPageBanners(int $orgId, int $userId, SeedImageLibrary $images): void
    {
        $map = [
            'about-us' => 'about',
            'contact-us' => 'contact',
            'privacy-policy' => 'privacy',
            'terms-and-conditions' => 'terms',
            'help-center' => 'about',
            'careers' => 'organization',
        ];

        foreach (SitePage::query()->where('organization_id', $orgId)->get() as $page) {
            $type = $map[$page->slug] ?? null;
            if (! $type) {
                continue;
            }

            $banner = $this->safeSeo($images, $orgId, $type, $userId, $page->title.' banner', 'page-'.$page->slug);
            if ($banner) {
                $page->update(['banner_image_id' => $banner->id]);
            }
        }
    }

    private function seedAdvertisements(int $orgId, int $userId, SeedImageLibrary $images): void
    {
        Advertisement::query()->where('organization_id', $orgId)->delete();

        $ads = [
            [
                'name' => 'Home sidebar premium mocks',
                'placement' => 'home_sidebar',
                'seo' => 'exam',
                'headline' => 'Upgrade your mock series',
                'body' => 'Unlock timed packs with analytics built for serious aspirants.',
                'cta_label' => 'Browse exams',
                'cta_url' => '/exams',
            ],
            [
                'name' => 'Exam list banner',
                'placement' => 'exam_list',
                'seo' => 'category',
                'headline' => 'Find your next paper',
                'body' => 'Aptitude, technical, and HR interview assessments ready to attempt.',
                'cta_label' => 'View categories',
                'cta_url' => '/categories',
            ],
            [
                'name' => 'Blog sidebar banner',
                'placement' => 'blog_detail_sidebar_top',
                'seo' => 'blog',
                'headline' => 'Practice after you read',
                'body' => 'Turn study tips into timed attempts on Examtube.',
                'cta_label' => 'Start practicing',
                'cta_url' => '/exams',
            ],
            [
                'name' => 'News sidebar banner',
                'placement' => 'news_detail_sidebar_top',
                'seo' => 'news',
                'headline' => 'News that fuels prep',
                'body' => 'Stay current, then validate readiness with a mock test.',
                'cta_label' => 'Open exams',
                'cta_url' => '/exams',
            ],
            [
                'name' => 'Exam result promo',
                'placement' => 'exam_result',
                'seo' => 'exam',
                'headline' => 'Retake. Improve. Repeat.',
                'body' => 'Use unlimited practice papers to climb your score curve.',
                'cta_label' => 'Try another exam',
                'cta_url' => '/exams',
            ],
            [
                'name' => 'Footer strip banner',
                'placement' => 'footer',
                'seo' => 'organization',
                'headline' => 'Examtube for institutes',
                'body' => 'Brand your workspace, publish exams, and track candidate results.',
                'cta_label' => 'Contact us',
                'cta_url' => '/contact-us',
            ],
        ];

        foreach ($ads as $index => $ad) {
            $image = $this->safeSeo($images, $orgId, $ad['seo'], $userId, $ad['name'], 'ad-'.$index);

            Advertisement::query()->create([
                'organization_id' => $orgId,
                'name' => $ad['name'],
                'type' => AdvertisementCatalog::TYPE_BANNER,
                'placement' => $ad['placement'],
                'headline' => $ad['headline'],
                'body' => $ad['body'],
                'cta_label' => $ad['cta_label'],
                'cta_url' => $ad['cta_url'],
                'image_id' => $image?->id,
                'mobile_image_id' => $image?->id,
                'sort_order' => $index + 1,
                'status' => 'active',
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addYear(),
            ]);
        }

        SiteSetting::query()->updateOrCreate(
            ['organization_id' => $orgId, 'group' => 'advertisements', 'key' => 'question_list_every_n'],
            [
                'value' => '2',
                'type' => 'integer',
                'label' => 'Insert ad every N questions',
            ]
        );
    }

    private function seedStandaloneGallery(int $orgId, int $userId, SeedImageLibrary $images): void
    {
        $types = ['home', 'exam', 'blog', 'news', 'category', 'question', 'organization', 'about'];

        foreach ($types as $index => $type) {
            $this->safeSeo(
                $images,
                $orgId,
                $type,
                $userId,
                'Campus gallery image '.($index + 1),
                'gallery-'.$index
            );
        }
    }

    private function syncOrganizationColumns(Organization $org, ?Gallery $logo, ?Gallery $banner): void
    {
        $org->forceFill([
            'logo' => $logo?->file_path,
            'banner' => $banner?->file_path,
            'meta_title' => 'Examtube.in — Online Exams, Mock Tests & Learning Hub',
            'meta_description' => 'Prepare for competitive exams with curated mock tests, expert blogs, campus news, and progress tracking on Examtube.in.',
            'meta_keywords' => 'online exams, mock tests, competitive exams, exam preparation, Examtube',
            'og_title' => 'Examtube.in',
            'og_description' => 'Practice smarter. Score higher. Get exam-ready.',
        ])->save();
    }

    private function upsertSetting(?int $orgId, string $group, string $key, mixed $value, string $type, string $label): void
    {
        if ($value === null) {
            return;
        }

        SiteSetting::query()->updateOrCreate(
            [
                'organization_id' => $orgId,
                'group' => $group,
                'key' => $key,
            ],
            [
                'value' => is_scalar($value) ? (string) $value : json_encode($value),
                'type' => $type,
                'label' => $label,
            ]
        );
    }

    private function safeSeo(
        SeedImageLibrary $images,
        int $orgId,
        string $type,
        int $userId,
        string $alt,
        ?string $suffix = null
    ): ?Gallery {
        try {
            $meta = [
                'alt_text' => $alt,
                'description' => $alt,
            ];
            if ($suffix !== null) {
                $meta['slug_suffix'] = $suffix;
            }

            return $images->storeSeoDefault($orgId, $type, $userId, 'demo-media', $meta);
        } catch (Throwable $e) {
            $this->command?->warn("DemoMediaSeeder: failed {$type}: {$e->getMessage()}");

            return null;
        }
    }
}
