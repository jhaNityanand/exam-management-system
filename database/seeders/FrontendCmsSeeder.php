<?php

namespace Database\Seeders;

use App\Models\Cms\Announcement;
use App\Models\Cms\Faq;
use App\Models\Cms\FaqCategory;
use App\Models\Cms\HeroBanner;
use App\Models\Cms\HomeSection;
use App\Models\Cms\Partner;
use App\Models\Cms\SiteMenu;
use App\Models\Cms\SiteMenuItem;
use App\Models\Cms\SitePage;
use App\Models\Cms\SiteSetting;
use App\Models\Cms\SocialLink;
use App\Models\Cms\Testimonial;
use App\Models\Organization;
use Illuminate\Database\Seeder;

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
        $this->seedPartners($orgId);
        $this->seedAnnouncements($orgId);

        // Active banner ads with images are created by DemoMediaSeeder.
        if ($orgId) {
            app(\App\Services\Advertisement\AdvertisementService::class)->seedDefaults($orgId);
        }
    }

    protected function seedSettings(?int $orgId): void
    {
        $settings = [
            ['group' => 'brand', 'key' => 'site_name', 'value' => 'Examtube.in', 'type' => 'string', 'label' => 'Site name'],
            ['group' => 'brand', 'key' => 'application_url', 'value' => 'https://examtube.in', 'type' => 'string', 'label' => 'Application URL'],
            ['group' => 'brand', 'key' => 'tagline', 'value' => 'Practice smarter. Score higher. Get exam-ready.', 'type' => 'string', 'label' => 'Tagline'],
            ['group' => 'brand', 'key' => 'logo_text', 'value' => 'Examtube', 'type' => 'string', 'label' => 'Logo text'],
            ['group' => 'brand', 'key' => 'description', 'value' => 'Examtube.in helps students, job seekers, and institutes practice with structured exams, stay updated with education news, and learn from practical blogs.', 'type' => 'text', 'label' => 'Description'],
            ['group' => 'contact', 'key' => 'email', 'value' => 'hello@examtube.in', 'type' => 'string', 'label' => 'Support email'],
            ['group' => 'contact', 'key' => 'phone', 'value' => '+91 98765 43210', 'type' => 'string', 'label' => 'Support phone'],
            ['group' => 'contact', 'key' => 'whatsapp', 'value' => '+91 98765 43210', 'type' => 'string', 'label' => 'WhatsApp'],
            ['group' => 'contact', 'key' => 'address', 'value' => 'Innov8 Workspace, Koramangala, Bengaluru, Karnataka 560034', 'type' => 'text', 'label' => 'Address'],
            ['group' => 'contact', 'key' => 'hours', 'value' => 'Monday 10:00 AM – 4:00 PM (IST); Tuesday 10:00 AM – 4:00 PM (IST); Wednesday 10:00 AM – 4:00 PM (IST); Thursday 10:00 AM – 4:00 PM (IST); Friday 10:00 AM – 4:00 PM (IST); Saturday 10:00 AM – 4:00 PM (IST)', 'type' => 'string', 'label' => 'Support hours'],
            ['group' => 'contact', 'key' => 'support_hours', 'value' => json_encode([
                ['day' => 'monday', 'from' => '10:00', 'to' => '16:00', 'timezone' => 'Asia/Kolkata'],
                ['day' => 'tuesday', 'from' => '10:00', 'to' => '16:00', 'timezone' => 'Asia/Kolkata'],
                ['day' => 'wednesday', 'from' => '10:00', 'to' => '16:00', 'timezone' => 'Asia/Kolkata'],
                ['day' => 'thursday', 'from' => '10:00', 'to' => '16:00', 'timezone' => 'Asia/Kolkata'],
                ['day' => 'friday', 'from' => '10:00', 'to' => '16:00', 'timezone' => 'Asia/Kolkata'],
                ['day' => 'saturday', 'from' => '10:00', 'to' => '16:00', 'timezone' => 'Asia/Kolkata'],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'type' => 'json', 'label' => 'Support hours schedule'],
            ['group' => 'contact', 'key' => 'maps_url', 'value' => 'https://maps.google.com/?q=Koramangala+Bengaluru', 'type' => 'string', 'label' => 'Google Maps URL'],
            ['group' => 'seo', 'key' => 'default_title', 'value' => 'Examtube.in — Online Exams, Mock Tests & Learning Hub', 'type' => 'string', 'label' => 'Default SEO title'],
            ['group' => 'seo', 'key' => 'default_description', 'value' => 'Prepare for competitive exams with curated mock tests, expert blogs, campus news, and progress tracking on Examtube.in.', 'type' => 'text', 'label' => 'Default SEO description'],
            ['group' => 'seo', 'key' => 'default_keywords', 'value' => 'online exams, mock tests, competitive exams, exam preparation, Examtube', 'type' => 'string', 'label' => 'Default keywords'],
            ['group' => 'footer', 'key' => 'about', 'value' => 'Examtube.in helps students, job seekers, and institutes practice with structured exams, stay updated with education news, and learn from practical blogs.', 'type' => 'text', 'label' => 'Footer about'],
            ['group' => 'footer', 'key' => 'copyright', 'value' => '© {year} Examtube.in. Built for aspirants across India.', 'type' => 'string', 'label' => 'Copyright'],
            ['group' => 'newsletter', 'key' => 'title', 'value' => 'Stay exam-ready every week', 'type' => 'string', 'label' => 'Newsletter title'],
            ['group' => 'newsletter', 'key' => 'subtitle', 'value' => 'Get curated exam alerts, practice tips, and career updates from Examtube.in — no spam, only useful prep.', 'type' => 'text', 'label' => 'Newsletter subtitle'],
            ['group' => 'newsletter', 'key' => 'cta', 'value' => 'Subscribe', 'type' => 'string', 'label' => 'Newsletter CTA'],
            ['group' => 'cta', 'key' => 'title', 'value' => 'Ready to start your next exam?', 'type' => 'string', 'label' => 'CTA title'],
            ['group' => 'cta', 'key' => 'subtitle', 'value' => 'Practice with structured mock tests, track scores, and learn with blogs & news built for exam aspirants.', 'type' => 'text', 'label' => 'CTA subtitle'],
            ['group' => 'cta', 'key' => 'primary_label', 'value' => 'Browse Exams', 'type' => 'string', 'label' => 'CTA primary label'],
            ['group' => 'cta', 'key' => 'primary_url', 'value' => '/exams', 'type' => 'string', 'label' => 'CTA primary URL'],
            ['group' => 'cta', 'key' => 'secondary_label', 'value' => 'Create free account', 'type' => 'string', 'label' => 'CTA secondary label'],
            ['group' => 'cta', 'key' => 'secondary_url', 'value' => '/register', 'type' => 'string', 'label' => 'CTA secondary URL'],
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

        app(\App\Services\Settings\MaintenanceModeService::class)->seedDefaults($orgId, [
            'enabled' => false,
            'title' => 'We will be right back',
            'message' => "We are currently performing scheduled maintenance to improve your experience.\nPlease check back shortly.",
            'contact_email' => 'hello@examtube.in',
            'contact_phone' => '+91 98765 43210',
            'social_facebook' => 'https://facebook.com/examtube',
            'social_instagram' => 'https://instagram.com/examtube',
            'social_linkedin' => 'https://linkedin.com/company/examtube',
            'social_twitter' => 'https://x.com/examtube',
            'social_youtube' => 'https://youtube.com/@examtube',
            'social_telegram' => 'https://t.me/examtube',
        ]);

        app(\App\Services\Seo\SeoSiteGenerator::class)->seedDefaults($orgId);

        app(\App\Services\Settings\EmailConfigurationService::class)->seedDefaults($orgId, [
            'from_address' => 'hello@examtube.in',
            'from_name' => 'Examtube.in',
        ]);

        app(\App\Services\Settings\IntegrationsSettingsService::class)->seedDefaults($orgId);
        app(\App\Services\Settings\SecuritySettingsService::class)->seedDefaults($orgId);
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
                'title' => 'Master every competitive exam with confidence',
                'subtitle' => 'Mock tests · Timed practice · Instant insights',
                'description' => 'Examtube.in brings structured exams, expert blogs, and exam news together so you prepare with clarity — not chaos.',
                'badge_text' => 'Trusted by aspirants across India',
                'primary_cta_label' => 'Explore exams',
                'primary_cta_url' => '/exams',
                'secondary_cta_label' => 'Read prep blogs',
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
                'title' => 'Stay informed with blogs & campus news',
                'subtitle' => 'Strategy · Alerts · Opportunities',
                'description' => 'Follow trending education news and practical study blogs from mentors who understand board exams, university tests, and government recruitment.',
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
            ['key' => 'partners', 'title' => 'Partners & sponsors', 'subtitle' => 'Institutes and brands supporting quality preparation', 'sort_order' => 90],
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
    }

    protected function seedPages(?int $orgId): void
    {
        $pages = [
            [
                'slug' => 'about-us',
                'title' => 'About Examtube.in',
                'template' => 'default',
                'excerpt' => 'We build exam-ready practice experiences for students, teachers, and institutes.',
                'content' => '<p>Examtube.in is an online exam management and learning platform created for aspirants who want structured practice, clear feedback, and reliable education updates in one place.</p><p>Our mission is simple: help every learner attempt exams with confidence, whether they are preparing for university assessments, competitive recruitments, or classroom evaluations.</p><h2>What we offer</h2><ul><li>Configurable mock exams with timers and scoring rules</li><li>Expert blogs for strategy and syllabus clarity</li><li>News updates that matter to candidates</li><li>Institute-friendly administration with galleries and media tools</li></ul>',
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
                'template' => 'default',
                'excerpt' => 'How Examtube.in collects, uses, and protects your information.',
                'content' => '<p>We respect your privacy. Account data, exam attempts, and newsletter subscriptions are processed only to deliver the learning experience you request.</p><p>We do not sell personal data. Access is limited to authorized operators of your organization workspace.</p>',
            ],
            [
                'slug' => 'terms-and-conditions',
                'title' => 'Terms and Conditions',
                'template' => 'default',
                'excerpt' => 'The rules that govern use of Examtube.in.',
                'content' => '<p>By creating an account or attempting an exam on Examtube.in, you agree to use the platform honestly, respect exam integrity, and keep your credentials secure.</p><p>Institutes are responsible for the exams and content they publish within their workspace.</p>',
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
                'content' => '<p>We welcome educators, full-stack engineers, content strategists, and growth partners who care about accessible education technology.</p><p>Email <strong>careers@examtube.in</strong> with your portfolio and the role you are excited about.</p>',
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
            ['name' => 'Ananya Sharma', 'role' => 'UPSC Aspirant', 'company' => 'Delhi', 'quote' => 'The timed mocks on Examtube feel closer to the real exam than anything else I used. Analytics showed exactly where I was losing marks.', 'rating' => 5],
            ['name' => 'Rahul Nair', 'role' => 'Engineering Student', 'company' => 'NIT Calicut', 'quote' => 'I prep with chapter quizzes after campus hours. The interface is clean, and blog tips for GATE were surprisingly practical.', 'rating' => 5],
            ['name' => 'Fatima Khan', 'role' => 'Banking Exam Coach', 'company' => 'Hyderabad Coaching Hub', 'quote' => 'We moved our institute mocks here. Candidates get consistent papers, and we manage categories without spreadsheet chaos.', 'rating' => 5],
            ['name' => 'Vikram Joshi', 'role' => 'Job Seeker', 'company' => 'Pune', 'quote' => 'Between SSC practice sets and daily news alerts, Examtube keeps my routine honest. I finally stopped hopping between five apps.', 'rating' => 4],
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
            ['name' => 'Getting started', 'slug' => 'getting-started'],
            ['name' => 'Exams & scoring', 'slug' => 'exams-scoring'],
            ['name' => 'Accounts', 'slug' => 'accounts'],
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
            ['slug' => 'getting-started', 'question' => 'How do I begin practicing on Examtube.in?', 'answer' => 'Create a free account, browse the Exams section, pick a published paper that matches your goal, and attempt it under timer conditions. Results appear after submission with marks and attempt history.'],
            ['slug' => 'getting-started', 'question' => 'Is Examtube suitable for institute classrooms?', 'answer' => 'Yes. Institutes can manage exams, categories, blogs, news, and media from the admin workspace while candidates attempt papers on the public site.'],
            ['slug' => 'exams-scoring', 'question' => 'Do practice exams support negative marking?', 'answer' => 'Yes. Exam authors can enable negative marking and pass percentage rules so mocks match the scoring pattern of the real paper.'],
            ['slug' => 'exams-scoring', 'question' => 'Can I retake an exam?', 'answer' => 'Retakes depend on the exam’s attempt policy. Some papers allow unlimited practice; others limit attempts to preserve exam integrity.'],
            ['slug' => 'accounts', 'question' => 'How do I reset my password?', 'answer' => 'Use Forgot password on the login page. You will receive a secure reset link on your registered email address.'],
            ['slug' => 'accounts', 'question' => 'Where can I track my progress?', 'answer' => 'After logging in, open your profile dashboard to review exam attempts, results, and saved reading preferences.'],
        ];

        foreach ($faqs as $i => $faq) {
            Faq::query()->create([
                'organization_id' => $orgId,
                'faq_category_id' => $categoryIds[$faq['slug']],
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'is_featured' => $i < 4,
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
            ['platform' => 'instagram', 'label' => 'Instagram', 'url' => 'https://instagram.com/examtube.in', 'sort_order' => 2],
            ['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'https://linkedin.com/company/examtube', 'sort_order' => 3],
            ['platform' => 'x', 'label' => 'X (Twitter)', 'url' => 'https://x.com/examtube', 'sort_order' => 4],
            ['platform' => 'youtube', 'label' => 'YouTube', 'url' => 'https://youtube.com/@examtube', 'sort_order' => 5],
            ['platform' => 'telegram', 'label' => 'Telegram', 'url' => 'https://t.me/examtube', 'sort_order' => 6],
            ['platform' => 'whatsapp', 'label' => 'WhatsApp', 'url' => 'https://wa.me/919876543210', 'sort_order' => 7],
            ['platform' => 'github', 'label' => 'GitHub', 'url' => 'https://github.com/examtube', 'sort_order' => 8],
            ['platform' => 'discord', 'label' => 'Discord', 'url' => 'https://discord.gg/examtube', 'sort_order' => 9],
        ] as $row) {
            SocialLink::query()->create(array_merge($row, [
                'organization_id' => $orgId,
                'is_visible' => true,
            ]));
        }
    }

    protected function seedPartners(?int $orgId): void
    {
        Partner::query()->where('organization_id', $orgId)->delete();

        foreach ([
            ['name' => 'SkillVista Academy', 'url' => 'https://skillvista.example', 'sort_order' => 1],
            ['name' => 'CampusBridge India', 'url' => 'https://campusbridge.example', 'sort_order' => 2],
            ['name' => 'HireReady Labs', 'url' => 'https://hireready.example', 'sort_order' => 3],
            ['name' => 'EduPulse Media', 'url' => 'https://edupulse.example', 'sort_order' => 4],
        ] as $row) {
            Partner::query()->create(array_merge($row, [
                'organization_id' => $orgId,
                'status' => 'active',
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
