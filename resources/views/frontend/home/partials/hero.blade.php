@php
    $featuredExam = ($page['featuredExams'] ?? collect())->first();
    $featuredBlog = ($page['featuredBlogs'] ?? collect())->first();
    $featuredNewsItem = ($page['latestNews'] ?? collect())->first();
    $featuredQuestion = ($page['randomQuestions'] ?? collect())->first();
    $featuredCategory = ($page['examCategories'] ?? ($page['categories'] ?? collect()))->first();

    $heroAsset = fn (string $file) => asset('frontend/images/hero/'.$file);

    $slides = [
        [
            'theme' => 'exams',
            'badge' => 'Exams',
            'title' => 'Timed mock exams that feel like the real thing',
            'description' => 'Practice with structured papers, scoring rules, and instant insights built for competitive aspirants.',
            'cta_label' => 'Browse exams',
            'cta_url' => route('frontend.exams.index'),
            'illustration' => $heroAsset('exams.svg'),
        ],
        [
            'theme' => 'blogs',
            'badge' => 'Blogs',
            'title' => 'Prep blogs that turn strategy into marks',
            'description' => 'Mentor-led guides, revision frameworks, and practical tips to keep your preparation sharp.',
            'cta_label' => 'Read blogs',
            'cta_url' => route('frontend.blogs.index'),
            'illustration' => $heroAsset('blogs.svg'),
        ],
        [
            'theme' => 'news',
            'badge' => 'News',
            'title' => 'Exam news that keeps you ahead of the curve',
            'description' => 'Notifications, deadlines, and updates curated so you never miss what matters.',
            'cta_label' => 'Latest news',
            'cta_url' => route('frontend.news.index'),
            'illustration' => $heroAsset('news.svg'),
        ],
        [
            'theme' => 'questions',
            'badge' => 'Questions',
            'title' => 'Question bank for concept-level mastery',
            'description' => 'Drill categorized questions with clear difficulty levels and focused practice sessions.',
            'cta_label' => 'Practice questions',
            'cta_url' => route('frontend.questions.index'),
            'illustration' => $heroAsset('questions.svg'),
        ],
        [
            'theme' => 'categories',
            'badge' => 'Categories',
            'title' => 'Browse by category and find your lane fast',
            'description' => 'Explore exams, blogs, news, and questions organized around the topics you care about.',
            'cta_label' => 'View categories',
            'cta_url' => route('frontend.categories.index'),
            'illustration' => $heroAsset('categories.svg'),
        ],
    ];

    if ($featuredExam) {
        $slides[] = [
            'theme' => 'featured-exams',
            'badge' => 'Featured Exam',
            'title' => $featuredExam->title,
            'description' => \Illuminate\Support\Str::limit(strip_tags((string) ($featuredExam->description ?? 'Attempt this featured mock and benchmark your readiness.')), 140),
            'cta_label' => 'Attempt exam',
            'cta_url' => route('frontend.exams.show', $featuredExam->slug),
            'illustration' => $heroAsset('featured-exams.svg'),
        ];
    } else {
        $slides[] = [
            'theme' => 'featured-exams',
            'badge' => 'Featured Exams',
            'title' => 'Spotlight mocks handpicked for serious aspirants',
            'description' => 'Jump into featured exams with timers, scoring, and performance tracking.',
            'cta_label' => 'Explore featured exams',
            'cta_url' => route('frontend.exams.index'),
            'illustration' => $heroAsset('featured-exams.svg'),
        ];
    }

    if ($featuredBlog) {
        $slides[] = [
            'theme' => 'featured-blogs',
            'badge' => 'Featured Blog',
            'title' => $featuredBlog->title,
            'description' => \Illuminate\Support\Str::limit(strip_tags((string) ($featuredBlog->excerpt ?? $featuredBlog->content ?? 'A featured prep read from Examtube mentors.')), 140),
            'cta_label' => 'Read article',
            'cta_url' => route('frontend.blogs.show', $featuredBlog->slug),
            'illustration' => $heroAsset('featured-blogs.svg'),
        ];
    } else {
        $slides[] = [
            'theme' => 'featured-blogs',
            'badge' => 'Featured Blogs',
            'title' => 'Must-read articles for smarter preparation',
            'description' => 'Discover featured blogs that explain concepts, strategies, and revision plans clearly.',
            'cta_label' => 'Browse blogs',
            'cta_url' => route('frontend.blogs.index'),
            'illustration' => $heroAsset('featured-blogs.svg'),
        ];
    }

    if ($featuredNewsItem) {
        $slides[] = [
            'theme' => 'featured-news',
            'badge' => 'Featured News',
            'title' => $featuredNewsItem->title,
            'description' => \Illuminate\Support\Str::limit(strip_tags((string) ($featuredNewsItem->excerpt ?? $featuredNewsItem->content ?? 'Stay current with this featured update.')), 140),
            'cta_label' => 'Read news',
            'cta_url' => route('frontend.news.show', $featuredNewsItem->slug),
            'illustration' => $heroAsset('featured-news.svg'),
        ];
    } else {
        $slides[] = [
            'theme' => 'featured-news',
            'badge' => 'Featured News',
            'title' => 'Headlines that shape your exam calendar',
            'description' => 'Featured updates on notifications, results, and important announcements.',
            'cta_label' => 'See all news',
            'cta_url' => route('frontend.news.index'),
            'illustration' => $heroAsset('featured-news.svg'),
        ];
    }

    if ($featuredQuestion) {
        $questionTitle = method_exists($featuredQuestion, 'publicTitle')
            ? $featuredQuestion->publicTitle()
            : \Illuminate\Support\Str::limit(strip_tags((string) $featuredQuestion->body), 80);

        $slides[] = [
            'theme' => 'featured-questions',
            'badge' => 'Featured Question',
            'title' => $questionTitle,
            'description' => 'Practice this featured question and strengthen the exact concept you need next.',
            'cta_label' => 'View question',
            'cta_url' => route('frontend.questions.show', $featuredQuestion),
            'illustration' => $heroAsset('featured-questions.svg'),
        ];
    } else {
        $slides[] = [
            'theme' => 'featured-questions',
            'badge' => 'Featured Questions',
            'title' => 'High-yield practice questions, ready to attempt',
            'description' => 'Featured picks from the question bank to accelerate concept revision.',
            'cta_label' => 'Start practicing',
            'cta_url' => route('frontend.questions.index'),
            'illustration' => $heroAsset('featured-questions.svg'),
        ];
    }

    if ($featuredCategory) {
        $slides[] = [
            'theme' => 'featured-categories',
            'badge' => 'Featured Category',
            'title' => $featuredCategory->name,
            'description' => \Illuminate\Support\Str::limit(strip_tags((string) ($featuredCategory->description ?? 'Explore this featured category and dive into related learning resources.')), 140),
            'cta_label' => 'Open category',
            'cta_url' => route('frontend.categories.show', $featuredCategory->slug),
            'illustration' => $heroAsset('featured-categories.svg'),
        ];
    } else {
        $slides[] = [
            'theme' => 'featured-categories',
            'badge' => 'Featured Categories',
            'title' => 'Topic paths curated for focused prep',
            'description' => 'Jump into featured categories spanning exams, blogs, news, and questions.',
            'cta_label' => 'Browse categories',
            'cta_url' => route('frontend.categories.index'),
            'illustration' => $heroAsset('featured-categories.svg'),
        ];
    }
@endphp

<section class="et-hero" data-hero-slider data-active-theme="{{ $slides[0]['theme'] ?? 'exams' }}" aria-roledescription="carousel" aria-label="Examtube modules">
    <div class="et-hero__glow" aria-hidden="true"></div>
    <div class="et-hero__pattern" aria-hidden="true"></div>

    <div class="et-container et-hero__shell">
        <div class="et-hero__slider" data-hero-track>
            @foreach($slides as $i => $slide)
                <article
                    class="et-hero__slide {{ $i === 0 ? 'is-active' : '' }}"
                    data-hero-slide
                    data-theme="{{ $slide['theme'] }}"
                    aria-hidden="{{ $i === 0 ? 'false' : 'true' }}"
                    aria-roledescription="slide"
                    aria-label="{{ ($i + 1).' of '.count($slides).': '.$slide['badge'] }}"
                >
                    <div class="et-hero__card">
                        <div class="et-hero__copy">
                            <span class="et-hero__badge">{{ $slide['badge'] }}</span>
                            <h1 class="et-hero__title">{{ $slide['title'] }}</h1>
                            <p class="et-hero__desc">{{ $slide['description'] }}</p>
                            <div class="et-hero__actions">
                                <a href="{{ $slide['cta_url'] }}" class="et-btn et-btn--primary et-hero__cta">
                                    {{ $slide['cta_label'] }}
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div class="et-hero__media" aria-hidden="true">
                            <div class="et-hero__media-orb"></div>
                            <img
                                class="et-hero__illustration"
                                src="{{ $slide['illustration'] }}"
                                alt=""
                                width="520"
                                height="390"
                                loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
                                decoding="async"
                            >
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="et-hero__controls">
            <div class="et-hero__dots" role="tablist" aria-label="Hero slides">
                @foreach($slides as $i => $slide)
                    <button
                        type="button"
                        class="et-hero__dot {{ $i === 0 ? 'is-active' : '' }}"
                        data-hero-dot
                        role="tab"
                        aria-label="{{ $slide['badge'] }}"
                        aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                    ></button>
                @endforeach
            </div>
        </div>
    </div>
    @include('frontend.partials.ad-placement', ['page' => 'home', 'position' => 'after_hero'])
</section>
