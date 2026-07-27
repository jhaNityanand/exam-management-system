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
use Database\Seeders\Support\SeedAssetGenerator;
use Database\Seeders\Support\SeedImageLibrary;
use Illuminate\Database\Seeder;
use Throwable;

/**
 * Attaches production-ready branding, hero, exam, page, partner, testimonial,
 * advertisement, and standalone gallery media for demo-org from public/seed.
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

        $assets = (new SeedAssetGenerator)->ensure();
        $this->command?->info("DemoMediaSeeder: seed assets created={$assets['created']} skipped={$assets['skipped']}.");

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
        $logo = $this->safeStore($images, $org->id, 'brand/logo.png', $userId, 'Examtube logo');
        $favicon = $this->safeStore($images, $org->id, 'brand/favicon.png', $userId, 'Examtube favicon');
        $og = $this->safeStore($images, $org->id, 'brand/og-image.jpg', $userId, 'Default Open Graph image');

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
                'desktop' => 'heroes/hero-01-exam-confidence.jpg',
                'mobile' => 'heroes/mobile-01.jpg',
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
                'desktop' => 'heroes/hero-02-practice-floor.jpg',
                'mobile' => 'heroes/mobile-02.jpg',
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
                'desktop' => 'heroes/hero-03-news-insights.jpg',
                'mobile' => 'heroes/mobile-03.jpg',
            ],
            [
                'title' => 'See exactly where marks are won or lost',
                'subtitle' => 'Attempt history · Score trends · Weak topics',
                'description' => 'Review graded attempts with clear pass/fail outcomes, section-level feedback, and a timeline that keeps your preparation accountable.',
                'badge_text' => 'Results that teach',
                'primary_cta_label' => 'View exams',
                'primary_cta_url' => '/exams',
                'secondary_cta_label' => 'Create free account',
                'secondary_cta_url' => '/register',
                'show_search' => true,
                'desktop' => 'heroes/hero-04-analytics.jpg',
                'mobile' => 'heroes/mobile-04.jpg',
            ],
            [
                'title' => 'From campus interviews to career-ready confidence',
                'subtitle' => 'Aptitude · Technical · HR rounds',
                'description' => 'Run structured interview assessments for institutes and self-prep tracks for candidates — with branding, galleries, and ads ready out of the box.',
                'badge_text' => 'Demo-ready workspace',
                'primary_cta_label' => 'Browse categories',
                'primary_cta_url' => '/categories',
                'secondary_cta_label' => 'Contact us',
                'secondary_cta_url' => '/contact-us',
                'show_search' => false,
                'desktop' => 'heroes/hero-05-career-ready.jpg',
                'mobile' => 'heroes/mobile-05.jpg',
            ],
        ];

        HeroBanner::query()->where('organization_id', $orgId)->delete();

        foreach ($slides as $index => $slide) {
            $desktop = $this->safeStore($images, $orgId, $slide['desktop'], $userId, $slide['title']);
            $mobile = $this->safeStore($images, $orgId, $slide['mobile'], $userId, $slide['title'].' (mobile)');

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
        $map = [
            'SkillVista Academy' => 'partners/partner-01-skillvista.png',
            'CampusBridge India' => 'partners/partner-02-campusbridge.png',
            'HireReady Labs' => 'partners/partner-03-hireready.png',
            'EduPulse Media' => 'partners/partner-04-edupulse.png',
        ];

        foreach (Partner::query()->where('organization_id', $orgId)->get() as $partner) {
            $path = $map[$partner->name] ?? null;
            if (! $path) {
                continue;
            }

            $logo = $this->safeStore($images, $orgId, $path, $userId, $partner->name.' logo');
            if ($logo) {
                $partner->update(['logo_id' => $logo->id]);
            }
        }
    }

    private function seedTestimonials(int $orgId, int $userId, SeedImageLibrary $images): void
    {
        $map = [
            'Ananya Sharma' => 'avatars/avatar-01-ananya.jpg',
            'Rahul Nair' => 'avatars/avatar-02-rahul.jpg',
            'Fatima Khan' => 'avatars/avatar-03-fatima.jpg',
            'Vikram Joshi' => 'avatars/avatar-04-vikram.jpg',
        ];

        foreach (Testimonial::query()->where('organization_id', $orgId)->get() as $row) {
            $path = $map[$row->name] ?? null;
            if (! $path) {
                continue;
            }

            $avatar = $this->safeStore($images, $orgId, $path, $userId, $row->name.' avatar');
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

        $index = 0;
        foreach ($exams as $exam) {
            $index++;
            $file = sprintf('exams/exam-banner-%02d.jpg', (($index - 1) % 12) + 1);
            $banner = $this->safeStore($images, $orgId, $file, $userId, $exam->title.' banner');
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
            'about-us' => 'pages/page-about.jpg',
            'contact-us' => 'pages/page-contact.jpg',
            'privacy-policy' => 'pages/page-privacy.jpg',
            'terms-and-conditions' => 'pages/page-terms.jpg',
            'help-center' => 'pages/page-help.jpg',
            'careers' => 'pages/page-careers.jpg',
        ];

        foreach (SitePage::query()->where('organization_id', $orgId)->get() as $page) {
            $path = $map[$page->slug] ?? null;
            if (! $path) {
                continue;
            }

            $banner = $this->safeStore($images, $orgId, $path, $userId, $page->title.' banner');
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
                'file' => 'ads/ad-home-sidebar.jpg',
                'headline' => 'Upgrade your mock series',
                'body' => 'Unlock timed packs with analytics built for serious aspirants.',
                'cta_label' => 'Browse exams',
                'cta_url' => '/exams',
            ],
            [
                'name' => 'Exam list banner',
                'placement' => 'exam_list',
                'file' => 'ads/ad-exam-list.jpg',
                'headline' => 'Find your next paper',
                'body' => 'Aptitude, technical, and HR interview assessments ready to attempt.',
                'cta_label' => 'View categories',
                'cta_url' => '/categories',
            ],
            [
                'name' => 'Blog sidebar banner',
                'placement' => 'blog_detail_sidebar_top',
                'file' => 'ads/ad-blog-sidebar.jpg',
                'headline' => 'Practice after you read',
                'body' => 'Turn study tips into timed attempts on Examtube.',
                'cta_label' => 'Start practicing',
                'cta_url' => '/exams',
            ],
            [
                'name' => 'News sidebar banner',
                'placement' => 'news_detail_sidebar_top',
                'file' => 'ads/ad-news-sidebar.jpg',
                'headline' => 'News that fuels prep',
                'body' => 'Stay current, then validate readiness with a mock test.',
                'cta_label' => 'Open exams',
                'cta_url' => '/exams',
            ],
            [
                'name' => 'Exam result promo',
                'placement' => 'exam_result',
                'file' => 'ads/ad-exam-result.jpg',
                'headline' => 'Retake. Improve. Repeat.',
                'body' => 'Use unlimited practice papers to climb your score curve.',
                'cta_label' => 'Try another exam',
                'cta_url' => '/exams',
            ],
            [
                'name' => 'Footer strip banner',
                'placement' => 'footer',
                'file' => 'ads/ad-footer.jpg',
                'headline' => 'Examtube for institutes',
                'body' => 'Brand your workspace, publish exams, and track candidate results.',
                'cta_label' => 'Contact us',
                'cta_url' => '/contact-us',
            ],
        ];

        foreach ($ads as $index => $ad) {
            $image = $this->safeStore($images, $orgId, $ad['file'], $userId, $ad['name']);

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
        for ($i = 1; $i <= 8; $i++) {
            $this->safeStore(
                $images,
                $orgId,
                sprintf('gallery/gallery-%02d.jpg', $i),
                $userId,
                'Campus gallery image '.$i
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

    private function safeStore(SeedImageLibrary $images, int $orgId, string $path, int $userId, string $alt): ?Gallery
    {
        try {
            return $images->storeFromPublicSeed($orgId, $path, $userId, 'demo-media', [
                'alt_text' => $alt,
                'description' => $alt,
            ]);
        } catch (Throwable $e) {
            $this->command?->warn("DemoMediaSeeder: failed {$path}: {$e->getMessage()}");

            return null;
        }
    }
}
