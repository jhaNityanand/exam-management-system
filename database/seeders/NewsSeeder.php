<?php

namespace Database\Seeders;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsTag;
use Database\Seeders\Concerns\ResolvesDemoContext;
use Database\Seeders\Support\SeedImageLibrary;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Throwable;

/**
 * Seeds realistic news articles with categories, tags, and gallery banners.
 */
class NewsSeeder extends Seeder
{
    use ResolvesDemoContext;

    public function run(): void
    {
        $org = $this->demoOrganization();
        $author = $this->demoEditor();

        if (! $org || ! $author) {
            $this->command?->warn('NewsSeeder: demo-org or editor missing. Skipping.');

            return;
        }

        $images = new SeedImageLibrary;
        $purged = $images->purge($org->id, 'news');
        $this->command?->info("NewsSeeder: purged {$purged} previously seeded news image(s).");

        // Replace previous demo news so re-seeds stay accurate and duplicate-free.
        News::query()
            ->withTrashed()
            ->where('organization_id', $org->id)
            ->forceDelete();

        $categories = $this->seedCategories($org->id, $author->id);
        $tags = $this->seedTags($org->id);
        $seeded = 0;

        foreach ($this->articles() as $index => $article) {
            $category = $categories[$article['category_slug']] ?? null;
            if (! $category) {
                $this->command?->warn("NewsSeeder: category [{$article['category_slug']}] missing. Skipping.");

                continue;
            }

            $slug = $article['slug'];

            try {
                $banner = $images->store(
                    $org->id,
                    'news-'.$slug,
                    $author->id,
                    'news',
                    [
                        'alt_text' => $article['title'],
                        'description' => 'Cover image for '.$article['title'],
                    ]
                );
            } catch (Throwable $e) {
                $this->command?->warn("NewsSeeder: image failed for {$slug}: {$e->getMessage()}");
                $banner = null;
            }

            $news = News::query()->create([
                'organization_id' => $org->id,
                'slug' => $slug,
                'news_category_id' => $category->id,
                'title' => $article['title'],
                'short_description' => $article['short_description'],
                'excerpt' => $article['excerpt'],
                'content' => $article['content'],
                'banner_image_id' => $banner?->id,
                'featured_image_id' => $banner?->id,
                'og_image_id' => $banner?->id,
                'author_id' => $author->id,
                'author_name' => $author->name,
                'status' => News::STATUS_PUBLISHED,
                'visibility' => News::VISIBILITY_PUBLIC,
                'is_featured' => $article['is_featured'],
                'is_breaking' => $article['is_breaking'],
                'is_trending' => $article['is_trending'],
                'published_at' => now()->subDays($index + 1)->subHours(random_int(1, 12)),
                'sort_order' => $index,
                'seo_title' => $article['seo_title'],
                'seo_description' => $article['seo_description'],
                'seo_keywords' => $article['seo_keywords'],
                'og_title' => $article['title'],
                'og_description' => $article['excerpt'],
                'robots' => 'index,follow',
                'created_by' => $author->id,
                'ai_generated' => false,
                'ai_improve' => false,
            ]);

            if ($banner) {
                $news->banners()->sync([$banner->id => ['sort_order' => 0]]);
            }

            $tagIds = collect($article['tags'])
                ->map(fn (string $name) => $tags[$name] ?? null)
                ->filter()
                ->values()
                ->all();
            $news->tags()->sync($tagIds);
            $seeded++;
        }

        $this->command?->info("NewsSeeder: seeded {$seeded} news articles with gallery banners.");
    }

    /**
     * @return array<string, NewsCategory>
     */
    private function seedCategories(int $orgId, int $authorId): array
    {
        $definitions = [
            ['slug' => 'campus-alerts', 'name' => 'Campus Alerts', 'sort' => 1],
            ['slug' => 'career-updates', 'name' => 'Career Updates', 'sort' => 2],
            ['slug' => 'exam-calendar', 'name' => 'Exam Calendar', 'sort' => 3],
            ['slug' => 'tech-education', 'name' => 'Tech Education', 'sort' => 4],
            ['slug' => 'placements', 'name' => 'Placements', 'sort' => 5],
        ];

        $map = [];
        foreach ($definitions as $item) {
            $map[$item['slug']] = NewsCategory::query()->updateOrCreate(
                ['organization_id' => $orgId, 'slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'status' => 'active',
                    'sort_order' => $item['sort'],
                    'created_by' => $authorId,
                    'description' => $item['name'].' for learners and institutions.',
                ]
            );
        }

        return $map;
    }

    /**
     * @return array<string, int>
     */
    private function seedTags(int $orgId): array
    {
        $names = [
            'SSC', 'Banking', 'GATE', 'UPSC', 'Placements', 'Mock Tests',
            'Laravel', 'PHP', 'JavaScript', 'Campus', 'Scholarships', 'Internships',
            'Exam Calendar',
        ];

        $map = [];
        foreach ($names as $name) {
            $tag = NewsTag::query()->firstOrCreate(
                [
                    'organization_id' => $orgId,
                    'slug' => Str::slug($name),
                ],
                [
                    'name' => $name,
                ]
            );
            $map[$name] = $tag->id;
        }

        return $map;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function articles(): array
    {
        return [
            [
                'slug' => 'summer-ssc-mock-test-window-opens',
                'category_slug' => 'campus-alerts',
                'title' => 'Summer SSC mock test window opens for Tier-I aspirants',
                'short_description' => 'Timed practice papers covering quantitative aptitude and reasoning go live this week.',
                'excerpt' => 'A new summer series of free SSC Tier-I mocks mirrors official timing, negative marking, and sectional balance.',
                'seo_title' => 'SSC Summer Mock Tests Now Open',
                'seo_description' => 'Practice SSC Tier-I with timed mocks, instant scoring, and topic analytics.',
                'seo_keywords' => 'ssc, mock test, tier-i, aptitude',
                'tags' => ['SSC', 'Mock Tests'],
                'is_breaking' => true,
                'is_featured' => true,
                'is_trending' => true,
                'content' => <<<'HTML'
<p>Candidates preparing for SSC Combined Graduate Level and CHSL examinations can now attempt a refreshed summer mock series designed around the latest Tier-I pattern.</p>
<p>Each paper runs with exam-day timers, instant scoring, and negative marking so students experience realistic pressure before the official window.</p>
<ul>
<li>Daily full-length mocks with sectional analytics</li>
<li>Topic tags for Quant, Reasoning, English, and GK</li>
<li>Revision playlists for frequently missed concepts</li>
</ul>
<p>Register on the practice portal, choose the SSC category, and start with one diagnostic paper before moving to timed sets.</p>
HTML,
            ],
            [
                'slug' => 'banking-recruitment-calendars-updated',
                'category_slug' => 'career-updates',
                'title' => 'Banking recruitment calendars updated for PO and Clerk exams',
                'short_description' => 'Stay ahead with confirmed probationary officer and clerk timelines.',
                'excerpt' => 'A roundup of major banking exam dates and a practical 60-day preparation plan.',
                'seo_title' => 'Updated Banking Exam Calendar',
                'seo_description' => 'Latest PO and Clerk exam timelines with a focused 60-day study plan.',
                'seo_keywords' => 'banking, po, clerk, recruitment',
                'tags' => ['Banking', 'Exam Calendar'],
                'is_breaking' => false,
                'is_featured' => true,
                'is_trending' => true,
                'content' => <<<'HTML'
<p>Institute mentors recommend sectional mocks early in the cycle, then full-length papers during the final month.</p>
<p>Key focus areas for the next recruitment wave include Data Interpretation, Puzzle sets, and Banking Awareness capsules refreshed weekly.</p>
<p>Aspirants should lock a weekly schedule: five weekday sectional drills, one weekend full mock, and a Monday error-log review.</p>
HTML,
            ],
            [
                'slug' => 'gate-cloud-computing-practice-labs',
                'category_slug' => 'tech-education',
                'title' => 'GATE practice labs expand with cloud computing tracks',
                'short_description' => 'New question banks cover distributed systems and DevOps fundamentals.',
                'excerpt' => 'Engineering students preparing for GATE can practice cloud-focused sets alongside traditional CS papers.',
                'seo_title' => 'GATE Cloud Computing Practice Labs',
                'seo_description' => 'Cloud, distributed systems, and DevOps practice sets for GATE aspirants.',
                'seo_keywords' => 'gate, cloud, devops, computer science',
                'tags' => ['GATE', 'Mock Tests'],
                'is_breaking' => false,
                'is_featured' => false,
                'is_trending' => true,
                'content' => <<<'HTML'
<p>Each set includes detailed explanations and topic tags so learners can revisit weak areas quickly.</p>
<p>The cloud track spans virtualization basics, consistency models, container orchestration concepts, and reliability patterns commonly tested in advanced CS papers.</p>
HTML,
            ],
            [
                'slug' => 'campus-placement-drive-schedule-july',
                'category_slug' => 'placements',
                'title' => 'July campus placement drive schedule released for partner colleges',
                'short_description' => 'Product, services, and internship tracks open across three weeks.',
                'excerpt' => 'Partner colleges receive the consolidated July placement calendar with eligibility cut-offs and assessment formats.',
                'seo_title' => 'July Campus Placement Schedule',
                'seo_description' => 'Placement drive dates, eligibility, and assessment formats for partner campuses.',
                'seo_keywords' => 'placements, campus, internships',
                'tags' => ['Placements', 'Internships', 'Campus'],
                'is_breaking' => true,
                'is_featured' => true,
                'is_trending' => false,
                'content' => <<<'HTML'
<p>Hiring partners will evaluate aptitude, coding fundamentals, and communication in a hybrid format.</p>
<p>Students are advised to complete at least three company-style mock interviews and refresh resume projects before week one.</p>
<ol>
<li>Week 1 — Service companies and internship pipelines</li>
<li>Week 2 — Product engineering and SDE roles</li>
<li>Week 3 — Analyst and support engineering tracks</li>
</ol>
HTML,
            ],
            [
                'slug' => 'upsc-prelims-current-affairs-capsule',
                'category_slug' => 'exam-calendar',
                'title' => 'UPSC Prelims current affairs capsule for the next 30 days',
                'short_description' => 'A structured revision plan covering economy, polity, and science & tech.',
                'excerpt' => 'Faculty compiled a 30-day current affairs sprint with daily quizzes and weekly consolidation tests.',
                'seo_title' => 'UPSC Prelims 30-Day Current Affairs Plan',
                'seo_description' => 'Daily current affairs revision for UPSC Prelims with quizzes and notes.',
                'seo_keywords' => 'upsc, prelims, current affairs',
                'tags' => ['UPSC', 'Mock Tests'],
                'is_breaking' => false,
                'is_featured' => true,
                'is_trending' => true,
                'content' => <<<'HTML'
<p>The capsule prioritizes high-yield themes: monetary policy updates, constitutional amendments in news, space missions, and climate agreements.</p>
<p>Each Sunday includes a 50-question mixed quiz with explanations mapped back to static syllabus topics.</p>
HTML,
            ],
            [
                'slug' => 'laravel-bootcamp-scholarship-announced',
                'category_slug' => 'tech-education',
                'title' => 'Need-based scholarships announced for Laravel developer bootcamp',
                'short_description' => 'Forty seats reserved for students from Tier-2 and Tier-3 campuses.',
                'excerpt' => 'A six-week Laravel bootcamp will cover routing, Eloquent, APIs, and deployment with mentor-led projects.',
                'seo_title' => 'Laravel Bootcamp Scholarships',
                'seo_description' => 'Apply for need-based seats in a six-week Laravel developer bootcamp.',
                'seo_keywords' => 'laravel, scholarship, bootcamp, php',
                'tags' => ['Laravel', 'PHP', 'Scholarships'],
                'is_breaking' => false,
                'is_featured' => true,
                'is_trending' => false,
                'content' => <<<'HTML'
<p>Applicants submit a short statement of purpose and a GitHub or CodeSandbox sample. Selection emphasizes curiosity and consistency over prior framework experience.</p>
<p>Scholars receive cloud credits for deployment labs and weekly office hours with senior engineers.</p>
HTML,
            ],
            [
                'slug' => 'javascript-interview-sprint-weekend',
                'category_slug' => 'placements',
                'title' => 'Weekend JavaScript interview sprint for final-year students',
                'short_description' => 'Closures, promises, DOM, and system design warm-ups in two intensive days.',
                'excerpt' => 'Placement cells are hosting a weekend JS sprint with live coding and peer review circles.',
                'seo_title' => 'JavaScript Interview Weekend Sprint',
                'seo_description' => 'Two-day JavaScript interview prep covering language fundamentals and coding drills.',
                'seo_keywords' => 'javascript, interview, placements',
                'tags' => ['JavaScript', 'Placements'],
                'is_breaking' => false,
                'is_featured' => false,
                'is_trending' => true,
                'content' => <<<'HTML'
<p>Day one focuses on language mechanics and debugging. Day two simulates whiteboard rounds with async patterns and UI state problems.</p>
<p>Participants leave with a personalized error log and a seven-day follow-up practice plan.</p>
HTML,
            ],
            [
                'slug' => 'national-aptitude-league-registrations',
                'category_slug' => 'campus-alerts',
                'title' => 'National Aptitude League registrations open for college teams',
                'short_description' => 'Team contests spanning quant, logical reasoning, and verbal ability.',
                'excerpt' => 'Colleges may register up to three teams of four students for the inter-campus aptitude league.',
                'seo_title' => 'National Aptitude League Registrations',
                'seo_description' => 'Register college teams for the national aptitude league with quant and reasoning rounds.',
                'seo_keywords' => 'aptitude, league, campus',
                'tags' => ['Campus', 'Mock Tests'],
                'is_breaking' => true,
                'is_featured' => false,
                'is_trending' => true,
                'content' => <<<'HTML'
<p>Rounds are proctored online with anti-cheat monitoring and instant leaderboards.</p>
<p>Top teams advance to an on-campus finale featuring puzzle relays and collaborative caselets.</p>
HTML,
            ],
            [
                'slug' => 'internship-fair-product-startups',
                'category_slug' => 'career-updates',
                'title' => 'Internship fair brings product startups to campus this month',
                'short_description' => 'Roles open in frontend, backend, data, and growth operations.',
                'excerpt' => 'Startups will shortlist interns through take-home tasks and 20-minute technical screens.',
                'seo_title' => 'Product Startup Internship Fair',
                'seo_description' => 'Meet product startups hiring interns for engineering and growth roles.',
                'seo_keywords' => 'internships, startups, campus',
                'tags' => ['Internships', 'Placements', 'Campus'],
                'is_breaking' => false,
                'is_featured' => true,
                'is_trending' => false,
                'content' => <<<'HTML'
<p>Students should prepare a one-page portfolio highlighting shipped features, measurable impact, and links to live demos.</p>
<p>Career services will host resume clinics two days before the fair.</p>
HTML,
            ],
            [
                'slug' => 'database-design-workshop-normalization',
                'category_slug' => 'tech-education',
                'title' => 'Hands-on workshop: database design and normalization clinic',
                'short_description' => 'From ER diagrams to indexing decisions with real schemas.',
                'excerpt' => 'Faculty and industry mentors will review learner schemas and suggest normalization and index improvements.',
                'seo_title' => 'Database Design Workshop',
                'seo_description' => 'Practical database normalization and indexing workshop for students.',
                'seo_keywords' => 'database, normalization, sql, workshop',
                'tags' => ['Campus', 'Mock Tests'],
                'is_breaking' => false,
                'is_featured' => false,
                'is_trending' => false,
                'content' => <<<'HTML'
<p>Bring a draft schema for a campus project. Mentors will walk through anomalies, foreign keys, and query plans using EXPLAIN.</p>
<p>Attendees receive a checklist covering 1NF–3NF, covering indexes, and transaction isolation basics.</p>
HTML,
            ],
            [
                'slug' => 'ssc-english-descriptive-practice-week',
                'category_slug' => 'exam-calendar',
                'title' => 'SSC English descriptive practice week with mentor feedback',
                'short_description' => 'Essay and letter writing drills with annotated reviews.',
                'excerpt' => 'A dedicated week for SSC descriptive English helps candidates improve structure, tone, and time management.',
                'seo_title' => 'SSC Descriptive English Practice Week',
                'seo_description' => 'Essay and letter practice for SSC with mentor annotations.',
                'seo_keywords' => 'ssc, english, descriptive, essay',
                'tags' => ['SSC', 'Mock Tests'],
                'is_breaking' => false,
                'is_featured' => false,
                'is_trending' => true,
                'content' => <<<'HTML'
<p>Each participant submits three essays and two official letters. Mentors return annotated PDFs highlighting coherence, vocabulary precision, and format compliance.</p>
HTML,
            ],
            [
                'slug' => 'php-83-study-circle-launches',
                'category_slug' => 'tech-education',
                'title' => 'PHP 8.3 study circle launches for backend aspirants',
                'short_description' => 'Weekly deep-dives into typed constants, readonly improvements, and testing habits.',
                'excerpt' => 'A peer study circle will meet every Wednesday to discuss modern PHP features with live coding demos.',
                'seo_title' => 'PHP 8.3 Study Circle',
                'seo_description' => 'Join a weekly PHP 8.3 study circle with demos and code reviews.',
                'seo_keywords' => 'php, php 8.3, study circle',
                'tags' => ['PHP', 'Laravel'],
                'is_breaking' => false,
                'is_featured' => true,
                'is_trending' => false,
                'content' => <<<'HTML'
<p>Sessions alternate between language features and framework integration notes useful for Laravel interviews.</p>
<p>Members maintain a shared gist of snippets and common pitfalls discovered during practice.</p>
HTML,
            ],
            [
                'slug' => 'scholarship-test-stem-women',
                'category_slug' => 'career-updates',
                'title' => 'STEM scholarship test dates announced for women in tech',
                'short_description' => 'Aptitude and coding rounds decide tuition support for certified programs.',
                'excerpt' => 'Eligible candidates can attempt an online scholarship test covering aptitude, SQL, and introductory programming.',
                'seo_title' => 'STEM Scholarship Test for Women in Tech',
                'seo_description' => 'Scholarship test dates and syllabus for women pursuing technology programs.',
                'seo_keywords' => 'scholarship, stem, women in tech',
                'tags' => ['Scholarships', 'Placements'],
                'is_breaking' => true,
                'is_featured' => true,
                'is_trending' => true,
                'content' => <<<'HTML'
<p>Shortlisted scholars receive mentorship, interview preparation credits, and partial tuition support for approved bootcamps.</p>
<p>Application deadline is two weeks before the first online attempt window.</p>
HTML,
            ],
            [
                'slug' => 'mock-hr-interview-marathon',
                'category_slug' => 'placements',
                'title' => 'Mock HR interview marathon scheduled before major drives',
                'short_description' => 'Behavioral rounds, salary conversations, and storytelling practice.',
                'excerpt' => 'Career coaches will run back-to-back mock HR interviews with structured feedback forms.',
                'seo_title' => 'Mock HR Interview Marathon',
                'seo_description' => 'Practice HR interviews with coaches before campus placement drives.',
                'seo_keywords' => 'hr interview, placements, soft skills',
                'tags' => ['Placements', 'Campus'],
                'is_breaking' => false,
                'is_featured' => false,
                'is_trending' => true,
                'content' => <<<'HTML'
<p>Students should prepare STAR stories for leadership, conflict, failure, and teamwork prompts.</p>
<p>Each session ends with a recording (optional) and a checklist of language improvements for clarity and confidence.</p>
HTML,
            ],
        ];
    }
}
