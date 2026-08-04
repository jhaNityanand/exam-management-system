<?php

namespace Database\Seeders;

use App\Models\Cms\Announcement;
use App\Models\Cms\Faq;
use App\Models\Cms\FaqCategory;
use App\Models\Cms\HeroBanner;
use App\Models\Cms\HomeSection;
use App\Models\Cms\SiteMenu;
use App\Models\Cms\SiteMenuItem;
use App\Models\Cms\SitePage;
use App\Models\Cms\SiteSetting;
use App\Models\Cms\SocialLink;
use App\Models\Cms\Testimonial;
use App\Models\Organization;
use App\Services\Advertisement\AdvertisementService;
use App\Services\Seo\SeoSiteGenerator;
use App\Services\Settings\EmailConfigurationService;
use App\Services\Settings\IntegrationsSettingsService;
use App\Services\Settings\MaintenanceModeService;
use App\Services\Settings\SecuritySettingsService;
use Database\Seeders\Support\SeederContact;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FrontendCmsSeeder extends Seeder
{
    public function run(): void
    {
        $orgId = Organization::query()->where('slug', 'demo-org')->value('id')
            ?? Organization::query()->value('id');

        $this->seedSettings($orgId);
        $this->seedMenus($orgId);
        $this->seedHero($orgId);
        $this->seedHomeSections($orgId);
        $this->seedPages($orgId);
        $this->seedTestimonials($orgId);
        $this->seedFaqs($orgId);
        $this->seedSocial($orgId);
        $this->seedAnnouncements($orgId);

        // Active banner ads with images are created by DemoMediaSeeder.
        if ($orgId) {
            app(AdvertisementService::class)->seedDefaults($orgId);
        }
    }

    protected function seedSettings(?int $orgId): void
    {
        $settings = [
            ['group' => 'brand', 'key' => 'site_name', 'value' => 'Examtube.in', 'type' => 'string', 'label' => 'Site name'],
            ['group' => 'brand', 'key' => 'application_url', 'value' => 'https://examtube.in', 'type' => 'string', 'label' => 'Application URL'],
            ['group' => 'brand', 'key' => 'tagline', 'value' => 'Learn, practice, and stay informed — in one place.', 'type' => 'string', 'label' => 'Tagline'],
            ['group' => 'brand', 'key' => 'logo_text', 'value' => 'Examtube', 'type' => 'string', 'label' => 'Logo text'],
            ['group' => 'brand', 'key' => 'description', 'value' => 'Examtube.in is a learning platform for online exams, blogs, news, articles, organizations, and curated learning content — built for students, mentors, and institutes.', 'type' => 'text', 'label' => 'Description'],
            ['group' => 'contact', 'key' => 'email', 'value' => SeederContact::EMAIL_SUPPORT, 'type' => 'string', 'label' => 'Support email'],
            ['group' => 'contact', 'key' => 'phone', 'value' => SeederContact::PHONE, 'type' => 'string', 'label' => 'Support phone'],
            ['group' => 'contact', 'key' => 'whatsapp', 'value' => SeederContact::PHONE, 'type' => 'string', 'label' => 'WhatsApp'],
            ['group' => 'contact', 'key' => 'address', 'value' => SeederContact::ADDRESS, 'type' => 'text', 'label' => 'Address'],
            ['group' => 'contact', 'key' => 'hours', 'value' => 'Monday 10:00 AM – 4:00 PM (IST); Tuesday 10:00 AM – 4:00 PM (IST); Wednesday 10:00 AM – 4:00 PM (IST); Thursday 10:00 AM – 4:00 PM (IST); Friday 10:00 AM – 4:00 PM (IST); Saturday 10:00 AM – 4:00 PM (IST)', 'type' => 'string', 'label' => 'Support hours'],
            ['group' => 'contact', 'key' => 'support_hours', 'value' => json_encode([
                ['day' => 'monday', 'from' => '10:00', 'to' => '16:00', 'timezone' => 'Asia/Kolkata'],
                ['day' => 'tuesday', 'from' => '10:00', 'to' => '16:00', 'timezone' => 'Asia/Kolkata'],
                ['day' => 'wednesday', 'from' => '10:00', 'to' => '16:00', 'timezone' => 'Asia/Kolkata'],
                ['day' => 'thursday', 'from' => '10:00', 'to' => '16:00', 'timezone' => 'Asia/Kolkata'],
                ['day' => 'friday', 'from' => '10:00', 'to' => '16:00', 'timezone' => 'Asia/Kolkata'],
                ['day' => 'saturday', 'from' => '10:00', 'to' => '16:00', 'timezone' => 'Asia/Kolkata'],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'type' => 'json', 'label' => 'Support hours schedule'],
            ['group' => 'contact', 'key' => 'maps_url', 'value' => SeederContact::MAPS_URL, 'type' => 'string', 'label' => 'Google Maps URL'],
            ['group' => 'seo', 'key' => 'default_title', 'value' => 'Examtube.in — Exams, Blogs, News, Articles & Learning', 'type' => 'string', 'label' => 'Default SEO title'],
            ['group' => 'seo', 'key' => 'default_description', 'value' => 'Practice online exams, read blogs and articles, follow education news, and manage learning content for your organization on Examtube.in.', 'type' => 'text', 'label' => 'Default SEO description'],
            ['group' => 'seo', 'key' => 'default_keywords', 'value' => 'online exams, blogs, news, articles, organizations, learning content, mock tests, Examtube', 'type' => 'string', 'label' => 'Default keywords'],
            ['group' => 'footer', 'key' => 'about', 'value' => 'Examtube.in brings exams, blogs, news, articles, organizations, and learning content together for students, mentors, and institutes.', 'type' => 'text', 'label' => 'Footer about'],
            ['group' => 'footer', 'key' => 'copyright', 'value' => '© {year} Examtube.in. Built for learners and organizations across India.', 'type' => 'string', 'label' => 'Copyright'],
            ['group' => 'newsletter', 'key' => 'title', 'value' => 'Stay ahead every week', 'type' => 'string', 'label' => 'Newsletter title'],
            ['group' => 'newsletter', 'key' => 'subtitle', 'value' => 'Get exam alerts, new blogs, news highlights, and learning updates from Examtube.in — useful content only.', 'type' => 'text', 'label' => 'Newsletter subtitle'],
            ['group' => 'newsletter', 'key' => 'cta', 'value' => 'Subscribe', 'type' => 'string', 'label' => 'Newsletter CTA'],
            ['group' => 'cta', 'key' => 'title', 'value' => 'Ready to learn and practice?', 'type' => 'string', 'label' => 'CTA title'],
            ['group' => 'cta', 'key' => 'subtitle', 'value' => 'Explore exams, blogs, news, and articles — or create an account to track progress with your organization.', 'type' => 'text', 'label' => 'CTA subtitle'],
            ['group' => 'cta', 'key' => 'primary_label', 'value' => 'Browse Exams', 'type' => 'string', 'label' => 'CTA primary label'],
            ['group' => 'cta', 'key' => 'primary_url', 'value' => '/exams', 'type' => 'string', 'label' => 'CTA primary URL'],
            ['group' => 'cta', 'key' => 'secondary_label', 'value' => 'Read blogs & news', 'type' => 'string', 'label' => 'CTA secondary label'],
            ['group' => 'cta', 'key' => 'secondary_url', 'value' => '/blogs', 'type' => 'string', 'label' => 'CTA secondary URL'],
            ['group' => 'company', 'key' => 'legal_name', 'value' => 'Examtube Learning Technologies', 'type' => 'string', 'label' => 'Legal name'],
            ['group' => 'company', 'key' => 'founded', 'value' => '2024', 'type' => 'string', 'label' => 'Founded year'],
        ];

        foreach ($settings as $row) {
            SiteSetting::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'group' => $row['group'],
                    'key' => $row['key'],
                ],
                [
                    'value' => $row['value'],
                    'type' => $row['type'],
                    'label' => $row['label'],
                ]
            );
        }

        app(MaintenanceModeService::class)->seedDefaults($orgId, [
            'enabled' => false,
            'title' => 'We will be right back',
            'message' => '<p>We are currently performing scheduled maintenance to improve your experience.</p><p>Please check back shortly.</p>',
            'social_facebook' => 'https://facebook.com/examtube',
            'social_instagram' => 'https://instagram.com/examtube',
            'social_linkedin' => 'https://linkedin.com/company/examtube',
            'social_twitter' => 'https://x.com/examtube',
            'social_youtube' => 'https://youtube.com/@examtube',
            'social_telegram' => 'https://t.me/examtube',
        ]);

        app(SeoSiteGenerator::class)->seedDefaults($orgId);
        try {
            app(SeoSiteGenerator::class)->generate($orgId);
        } catch (\Throwable) {
            // SEO file generation is best-effort during seeding (filesystem/URL config).
        }

        app(EmailConfigurationService::class)->seedDefaults($orgId, [
            'from_address' => SeederContact::EMAIL_SUPPORT,
            'from_name' => 'Examtube.in',
        ]);

        app(IntegrationsSettingsService::class)->seedDefaults($orgId);
        app(SecuritySettingsService::class)->seedDefaults($orgId);
    }

    protected function seedMenus(?int $orgId): void
    {
        $header = SiteMenu::query()->updateOrCreate(
            ['organization_id' => $orgId, 'location' => 'header'],
            ['name' => 'Primary Header', 'status' => 'active']
        );

        $headerItems = [
            ['label' => 'Home', 'type' => 'route', 'route_name' => 'home', 'sort_order' => 1],
            ['label' => 'Exams', 'type' => 'route', 'route_name' => 'frontend.exams.index', 'sort_order' => 2],
            ['label' => 'Blogs', 'type' => 'route', 'route_name' => 'frontend.blogs.index', 'sort_order' => 3],
            ['label' => 'News', 'type' => 'route', 'route_name' => 'frontend.news.index', 'sort_order' => 4],
            ['label' => 'Categories', 'type' => 'route', 'route_name' => 'frontend.categories.index', 'sort_order' => 5],
            ['label' => 'About Us', 'type' => 'page', 'page_slug' => 'about-us', 'sort_order' => 6],
            ['label' => 'Contact', 'type' => 'page', 'page_slug' => 'contact-us', 'sort_order' => 7],
        ];

        $header->items()->delete();
        foreach ($headerItems as $item) {
            SiteMenuItem::query()->create(array_merge($item, [
                'menu_id' => $header->id,
                'is_visible' => true,
                'target' => '_self',
            ]));
        }

        $footer = SiteMenu::query()->updateOrCreate(
            ['organization_id' => $orgId, 'location' => 'footer'],
            ['name' => 'Footer Explore', 'status' => 'active']
        );
        $footer->items()->delete();
        foreach ([
            ['label' => 'All Exams', 'type' => 'route', 'route_name' => 'frontend.exams.index', 'sort_order' => 1],
            ['label' => 'Latest Blogs', 'type' => 'route', 'route_name' => 'frontend.blogs.index', 'sort_order' => 2],
            ['label' => 'Campus News', 'type' => 'route', 'route_name' => 'frontend.news.index', 'sort_order' => 3],
            ['label' => 'Help Center', 'type' => 'page', 'page_slug' => 'help-center', 'sort_order' => 4],
            ['label' => 'Careers', 'type' => 'page', 'page_slug' => 'careers', 'sort_order' => 5],
        ] as $item) {
            SiteMenuItem::query()->create(array_merge($item, ['menu_id' => $footer->id, 'is_visible' => true, 'target' => '_self']));
        }

        $legal = SiteMenu::query()->updateOrCreate(
            ['organization_id' => $orgId, 'location' => 'footer_legal'],
            ['name' => 'Footer Legal', 'status' => 'active']
        );
        $legal->items()->delete();
        foreach ([
            ['label' => 'Privacy Policy', 'type' => 'page', 'page_slug' => 'privacy-policy', 'sort_order' => 1],
            ['label' => 'Terms & Conditions', 'type' => 'page', 'page_slug' => 'terms-and-conditions', 'sort_order' => 2],
            ['label' => 'Contact Us', 'type' => 'page', 'page_slug' => 'contact-us', 'sort_order' => 3],
        ] as $item) {
            SiteMenuItem::query()->create(array_merge($item, ['menu_id' => $legal->id, 'is_visible' => true, 'target' => '_self']));
        }

        $mobile = SiteMenu::query()->updateOrCreate(
            ['organization_id' => $orgId, 'location' => 'mobile'],
            ['name' => 'Mobile Nav', 'status' => 'active']
        );
        $mobile->items()->delete();
        foreach ($headerItems as $item) {
            SiteMenuItem::query()->create(array_merge($item, [
                'menu_id' => $mobile->id,
                'is_visible' => true,
                'target' => '_self',
            ]));
        }
    }

    protected function seedHero(?int $orgId): void
    {
        HeroBanner::query()->where('organization_id', $orgId)->delete();

        $slides = [
            [
                'title' => 'Learn, practice, and stay informed',
                'subtitle' => 'Exams · Blogs · News · Articles',
                'description' => 'Examtube.in brings online exams, blogs, news, articles, organizations, and learning content together so you prepare and grow with clarity.',
                'badge_text' => 'Trusted learning platform',
                'primary_cta_label' => 'Explore exams',
                'primary_cta_url' => '/exams',
                'secondary_cta_label' => 'Read blogs',
                'secondary_cta_url' => '/blogs',
                'show_search' => true,
                'sort_order' => 1,
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
                'sort_order' => 2,
            ],
            [
                'title' => 'Blogs, news, and articles for every learner',
                'subtitle' => 'Strategy · Alerts · Opportunities',
                'description' => 'Follow education news, mentor blogs, and practical articles — alongside organization workspaces and curated learning content.',
                'badge_text' => 'Updated daily',
                'primary_cta_label' => 'Open newsroom',
                'primary_cta_url' => '/news',
                'secondary_cta_label' => 'Browse blogs',
                'secondary_cta_url' => '/blogs',
                'show_search' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($slides as $slide) {
            HeroBanner::query()->create(array_merge($slide, [
                'organization_id' => $orgId,
                'status' => 'active',
                'theme' => 'emerald',
            ]));
        }
    }

    protected function seedHomeSections(?int $orgId): void
    {
        $sections = [
            ['key' => 'hero', 'title' => null, 'subtitle' => null, 'sort_order' => 10],
            ['key' => 'stats', 'title' => 'Why aspirants choose Examtube', 'subtitle' => 'Live numbers from our learning platform', 'sort_order' => 20],
            ['key' => 'featured_exams', 'title' => 'Featured exams', 'subtitle' => 'High-impact mocks and practice papers ready to attempt', 'sort_order' => 30],
            ['key' => 'categories', 'title' => 'Browse by category', 'subtitle' => 'Find exams by competitive stream and topic area', 'sort_order' => 40],
            ['key' => 'blogs', 'title' => 'Latest from the blog', 'subtitle' => 'Strategies, study plans, and mentor tips', 'sort_order' => 50],
            ['key' => 'news', 'title' => 'Education news desk', 'subtitle' => 'Breaking alerts and trending updates for candidates', 'sort_order' => 60],
            ['key' => 'testimonials', 'title' => 'Stories from learners', 'subtitle' => 'Real outcomes from students and job seekers', 'sort_order' => 70],
            ['key' => 'faqs', 'title' => 'Frequently asked questions', 'subtitle' => 'Quick answers before you begin', 'sort_order' => 80],
            ['key' => 'newsletter', 'title' => null, 'subtitle' => null, 'sort_order' => 100],
            ['key' => 'cta', 'title' => null, 'subtitle' => null, 'sort_order' => 110],
        ];

        foreach ($sections as $section) {
            HomeSection::query()->updateOrCreate(
                ['organization_id' => $orgId, 'key' => $section['key']],
                [
                    'title' => $section['title'],
                    'subtitle' => $section['subtitle'],
                    'is_enabled' => true,
                    'sort_order' => $section['sort_order'],
                    'settings' => [],
                ]
            );
        }

        // Remove legacy partners section if it still exists from older seeds.
        HomeSection::query()
            ->where('organization_id', $orgId)
            ->where('key', 'partners')
            ->delete();

        if (Schema::hasTable('partners')) {
            DB::table('partners')
                ->where('organization_id', $orgId)
                ->delete();
        }
    }

    protected function seedPages(?int $orgId): void
    {
        $pages = [
            [
                'slug' => 'about-us',
                'title' => 'About Us',
                'template' => 'about',
                'excerpt' => 'Examtube is a complete learning and knowledge platform for exams, questions, blogs, news, and career preparation.',
                'content' => '<p>Examtube is rendered with a dedicated About experience.</p>',
            ],
            [
                'slug' => 'contact-us',
                'title' => 'Contact Us',
                'template' => 'contact',
                'excerpt' => 'Talk to the Examtube support and partnerships team.',
                'content' => '<p>Have a question about exams, institute onboarding, or your account? Share a message and our team will respond during support hours.</p>',
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'template' => 'privacy',
                'excerpt' => 'How Examtube collects, uses, and protects your information across learning and exam features.',
                'content' => '<p>Privacy policy content is rendered with a dedicated legal layout.</p>',
            ],
            [
                'slug' => 'terms-and-conditions',
                'title' => 'Terms & Conditions',
                'template' => 'terms',
                'excerpt' => 'Rules for accounts, exams, content usage, and fair use of the Examtube platform.',
                'content' => '<p>Terms content is rendered with a dedicated legal layout.</p>',
            ],
            [
                'slug' => 'help-center',
                'title' => 'Help Center',
                'template' => 'help',
                'excerpt' => 'Guides for aspirants and institute admins.',
                'content' => '<p>Browse FAQs or contact support if you need help starting an exam, resetting a password, or managing an institute workspace.</p>',
            ],
            [
                'slug' => 'careers',
                'title' => 'Careers at Examtube',
                'template' => 'careers',
                'excerpt' => 'Join a team building better exam experiences for India.',
                'content' => '<p>We welcome educators, full-stack engineers, content strategists, and growth partners who care about accessible education technology.</p><p>Email <strong>'.SeederContact::EMAIL_INFO.'</strong> with your portfolio and the role you are excited about.</p>',
            ],
        ];

        foreach ($pages as $page) {
            SitePage::query()->updateOrCreate(
                ['organization_id' => $orgId, 'slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'template' => $page['template'],
                    'excerpt' => $page['excerpt'],
                    'content' => $page['content'],
                    'seo_title' => $page['title'].' | Examtube.in',
                    'seo_description' => $page['excerpt'],
                    'ai_generated' => false,
                    'ai_improve' => false,
                    'is_ai_generated' => false,
                    'is_sitemap_url_created' => false,
                    'status' => 'published',
                    'published_at' => now(),
                ]
            );
        }
    }

    protected function seedTestimonials(?int $orgId): void
    {
        Testimonial::query()->where('organization_id', $orgId)->delete();

        $items = [
            ['name' => 'Ananya Sharma', 'role' => 'UPSC Aspirant', 'company' => 'Delhi', 'quote' => 'Timed mocks finally felt like the real exam. Tracking weak topics week by week made a huge difference.', 'rating' => 5],
            ['name' => 'Rahul Nair', 'role' => 'Engineering Student', 'company' => 'NIT Calicut', 'quote' => 'I prep with chapter quizzes after campus hours. The interface is clean, and blog tips were surprisingly practical.', 'rating' => 5],
            ['name' => 'Fatima Khan', 'role' => 'Banking Exam Coach', 'company' => 'Hyderabad Coaching Hub', 'quote' => 'We moved our institute mocks here. Candidates get consistent papers, and we manage categories without spreadsheet chaos.', 'rating' => 5],
            ['name' => 'Vikram Joshi', 'role' => 'Mentor', 'company' => 'Pune Academy', 'quote' => 'Our coaching batch uses Examtube for weekly assessments. Students love the clarity of results.', 'rating' => 5],
            ['name' => 'Sneha Patel', 'role' => 'Banking Exam Candidate', 'company' => 'Ahmedabad', 'quote' => 'Questions with explanations saved me hours. I jump between categories without losing focus.', 'rating' => 4],
            ['name' => 'Arjun Mehta', 'role' => 'MBA Aspirant', 'company' => 'Mumbai', 'quote' => 'News alerts and exam calendar posts keep me ahead of deadlines. Simple and reliable.', 'rating' => 5],
            ['name' => 'Meera Iyer', 'role' => 'Campus Placement Prep', 'company' => 'Chennai', 'quote' => 'From free practice papers to paid proctored mocks — everything feels intentional and polished.', 'rating' => 5],
            ['name' => 'Karan Desai', 'role' => 'SSC Aspirant', 'company' => 'Surat', 'quote' => 'Sectional tests helped me rebuild speed in Quant. I finally stopped guessing under pressure.', 'rating' => 5],
            ['name' => 'Priya Menon', 'role' => 'NEET Mentor', 'company' => 'Kochi', 'quote' => 'My students revise with short quizzes between classes. The category filters keep sessions focused.', 'rating' => 4],
            ['name' => 'Imran Sheikh', 'role' => 'State PCS Candidate', 'company' => 'Lucknow', 'quote' => 'Detailed solutions after every mock made my revision loop tighter. Less panic, more clarity.', 'rating' => 5],
            ['name' => 'Neha Gupta', 'role' => 'Working Professional', 'company' => 'Bengaluru', 'quote' => 'I only get evenings to study. Timed practice packs fit my schedule without wasting a minute.', 'rating' => 5],
            ['name' => 'Rohan Kapoor', 'role' => 'Gate Aspirant', 'company' => 'Chandigarh', 'quote' => 'Topic-wise analytics showed my weak areas early. That alone changed how I planned the final month.', 'rating' => 4],
        ];

        foreach ($items as $i => $item) {
            Testimonial::query()->create(array_merge($item, [
                'organization_id' => $orgId,
                'is_featured' => true,
                'sort_order' => $i + 1,
                'status' => 'active',
            ]));
        }
    }

    protected function seedFaqs(?int $orgId): void
    {
        $cats = [
            ['name' => 'Getting started',        'slug' => 'getting-started'],
            ['name' => 'Exams & scoring',         'slug' => 'exams-scoring'],
            ['name' => 'Accounts',               'slug' => 'accounts'],
            ['name' => 'Institutes & admins',    'slug' => 'institutes-admins'],
            ['name' => 'Results & certificates', 'slug' => 'results-certificates'],
        ];

        $categoryIds = [];
        foreach ($cats as $i => $cat) {
            $model = FaqCategory::query()->updateOrCreate(
                ['organization_id' => $orgId, 'slug' => $cat['slug']],
                ['name' => $cat['name'], 'status' => 'active', 'sort_order' => $i + 1]
            );
            $categoryIds[$cat['slug']] = $model->id;
        }

        Faq::query()->where('organization_id', $orgId)->delete();

        $faqs = [
            // Getting started
            ['slug' => 'getting-started', 'featured' => true,  'question' => 'How do I begin practicing on Examtube.in?', 'answer' => 'Create a free account, browse the Exams section, pick a published paper that matches your goal, and attempt it under timer conditions. Results appear after submission with marks and attempt history.'],
            ['slug' => 'getting-started', 'featured' => true,  'question' => 'Is Examtube suitable for institute classrooms?', 'answer' => 'Yes. Institutes can manage exams, categories, blogs, news, and media from the admin workspace while candidates attempt papers on the public site.'],
            ['slug' => 'getting-started', 'featured' => false, 'question' => 'Do I need to pay to take exams?', 'answer' => 'Most practice exams are completely free. Some premium or institute-specific papers may require registration or an access code provided by your institute.'],
            ['slug' => 'getting-started', 'featured' => false, 'question' => 'Which browsers and devices are supported?', 'answer' => 'Examtube.in works on all modern browsers — Chrome, Firefox, Edge, and Safari — on both desktop and mobile devices. We recommend the latest browser version for the best experience.'],

            // Exams & scoring
            ['slug' => 'exams-scoring', 'featured' => true,  'question' => 'Do practice exams support negative marking?', 'answer' => 'Yes. Exam authors can enable negative marking and pass percentage rules so mocks match the scoring pattern of the real paper.'],
            ['slug' => 'exams-scoring', 'featured' => true,  'question' => 'Can I retake an exam?', 'answer' => 'Retakes depend on the exam\'s attempt policy. Some papers allow unlimited practice; others limit attempts to preserve exam integrity.'],
            ['slug' => 'exams-scoring', 'featured' => false, 'question' => 'Are questions shuffled in every attempt?', 'answer' => 'Shuffle behavior is controlled by the exam author. Many practice papers randomize both question order and answer options to simulate actual exam conditions.'],
            ['slug' => 'exams-scoring', 'featured' => false, 'question' => 'How is the final score calculated?', 'answer' => 'Each question carries a configurable mark value. Correct answers add marks; wrong answers subtract marks if negative marking is enabled. The final score is displayed as raw marks and, where applicable, as a percentage.'],
            ['slug' => 'exams-scoring', 'featured' => false, 'question' => 'Can I pause an exam midway?', 'answer' => 'Most timed exams do not support pausing, as this would compromise exam integrity. Check the exam instructions before starting to understand the timer and submission rules.'],

            // Accounts
            ['slug' => 'accounts', 'featured' => true,  'question' => 'How do I reset my password?', 'answer' => 'Use Forgot password on the login page. You will receive a secure reset link on your registered email address.'],
            ['slug' => 'accounts', 'featured' => false, 'question' => 'Where can I track my progress?', 'answer' => 'After logging in, open your profile dashboard to review exam attempts, results, and saved reading preferences.'],
            ['slug' => 'accounts', 'featured' => false, 'question' => 'Can I change my registered email address?', 'answer' => 'Yes. Go to Profile → Account settings and update your email. A verification link will be sent to the new address before the change takes effect.'],
            ['slug' => 'accounts', 'featured' => false, 'question' => 'How do I delete my account?', 'answer' => 'Send a deletion request to '.SeederContact::EMAIL_SUPPORT.' from your registered email address. Accounts are permanently removed within 7 business days. Attempt history cannot be recovered after deletion.'],

            // Institutes & admins
            ['slug' => 'institutes-admins', 'featured' => false, 'question' => 'How do I set up an institute workspace?', 'answer' => 'After registering, navigate to Admin → Settings → Organization to configure your branding, contact details, and homepage content. You can then create exam categories, publish exams, and manage candidates from the admin panel.'],
            ['slug' => 'institutes-admins', 'featured' => false, 'question' => 'Can I restrict an exam to specific candidates?', 'answer' => 'Yes. Exams can be set to require login, and you can control access through invitation codes or candidate lists configured in the exam settings.'],
            ['slug' => 'institutes-admins', 'featured' => false, 'question' => 'How many exams can I publish?', 'answer' => 'There is no hard limit on the number of exams you can create and publish within your workspace. Large-scale deployments can contact support for dedicated assistance.'],
            ['slug' => 'institutes-admins', 'featured' => false, 'question' => 'Can multiple admins manage the same workspace?', 'answer' => 'Yes. The primary organization admin can invite additional team members and assign roles with appropriate permissions from Admin → Settings → Team.'],

            // Results & certificates
            ['slug' => 'results-certificates', 'featured' => false, 'question' => 'When can I see my results after submitting an exam?', 'answer' => 'Results are available immediately after submission for most auto-graded exams. Your score, correct/incorrect breakdown, and rank (if enabled) appear on the results page.'],
            ['slug' => 'results-certificates', 'featured' => false, 'question' => 'Are certificates issued for completed exams?', 'answer' => 'Certificates are issued when the exam author has enabled them and you meet the required pass percentage. Download your certificate from the results page or your profile dashboard.'],
            ['slug' => 'results-certificates', 'featured' => false, 'question' => 'How long are my results stored?', 'answer' => 'Attempt records and results are stored for the lifetime of your account. Institutes retain records as configured by their data retention policy.'],
        ];

        foreach ($faqs as $i => $faq) {
            Faq::query()->create([
                'organization_id' => $orgId,
                'faq_category_id' => $categoryIds[$faq['slug']],
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'is_featured' => $faq['featured'],
                'sort_order' => $i + 1,
                'status' => 'active',
            ]);
        }
    }

    protected function seedSocial(?int $orgId): void
    {
        SocialLink::query()->where('organization_id', $orgId)->delete();

        foreach ([
            ['platform' => 'facebook', 'label' => 'Facebook', 'url' => 'https://facebook.com/examtube', 'sort_order' => 1],
            ['platform' => 'instagram', 'label' => 'Instagram', 'url' => 'https://instagram.com/examtube', 'sort_order' => 2],
            ['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'https://linkedin.com/company/examtube', 'sort_order' => 3],
            ['platform' => 'x', 'label' => 'X (Twitter)', 'url' => 'https://x.com/examtube', 'sort_order' => 4],
            ['platform' => 'youtube', 'label' => 'YouTube', 'url' => 'https://youtube.com/@examtube', 'sort_order' => 5],
            ['platform' => 'telegram', 'label' => 'Telegram', 'url' => 'https://t.me/examtube', 'sort_order' => 6],
            ['platform' => 'whatsapp', 'label' => 'WhatsApp', 'url' => SeederContact::WHATSAPP_URL, 'sort_order' => 7],
            ['platform' => 'github', 'label' => 'GitHub', 'url' => 'https://github.com/examtube', 'sort_order' => 8],
            ['platform' => 'discord', 'label' => 'Discord', 'url' => 'https://discord.gg/examtube', 'sort_order' => 9],
        ] as $row) {
            SocialLink::query()->create(array_merge($row, [
                'organization_id' => $orgId,
                'is_visible' => true,
            ]));
        }
    }

    protected function seedAnnouncements(?int $orgId): void
    {
        Announcement::query()->where('organization_id', $orgId)->delete();

        Announcement::query()->create([
            'organization_id' => $orgId,
            'title' => 'New summer mock series is live',
            'message' => 'Attempt timed papers for SSC, banking, and engineering entrances with updated syllabi for this season.',
            'type' => 'info',
            'cta_label' => 'View exams',
            'cta_url' => '/exams',
            'is_dismissible' => true,
            'sort_order' => 1,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonths(2),
        ]);
    }
}
