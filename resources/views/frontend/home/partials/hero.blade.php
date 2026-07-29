@php
    $slides = [
        [
            'badge' => 'Examtube.in',
            'title' => 'Master every competitive exam with confidence',
            'description' => 'Timed mocks, curated questions, and mentor-led blogs — built for serious aspirants.',
            'cta_label' => 'Explore exams',
            'cta_url' => route('frontend.exams.index'),
            'secondary_label' => 'Read prep blogs',
            'secondary_url' => route('frontend.blogs.index'),
            'illustration' => asset('frontend/images/banner.svg'),
            'show_search' => true,
        ],
        [
            'badge' => 'Practice Exams',
            'title' => 'Simulate the real test before the real day',
            'description' => 'Structured papers with timers, scoring rules, and instant performance insights.',
            'cta_label' => 'Browse exams',
            'cta_url' => route('frontend.exams.index'),
            'illustration' => asset('frontend/images/exams.svg'),
        ],
        [
            'badge' => 'Question Bank',
            'title' => 'Sharpen concepts one question at a time',
            'description' => 'Browse categorized practice questions with clear explanations and difficulty levels.',
            'cta_label' => 'Practice questions',
            'cta_url' => route('frontend.questions.index'),
            'illustration' => asset('frontend/images/questions.svg'),
        ],
        [
            'badge' => 'Learn & Stay Updated',
            'title' => 'Blogs and news that keep you exam-ready',
            'description' => 'Practical guides and timely updates so your preparation never stalls.',
            'cta_label' => 'Read blogs',
            'cta_url' => route('frontend.blogs.index'),
            'secondary_label' => 'Latest news',
            'secondary_url' => route('frontend.news.index'),
            'illustration' => asset('frontend/images/blogs.svg'),
        ],
    ];
@endphp
<section class="et-hero" data-hero-slider>
    <div class="et-hero__glow" aria-hidden="true"></div>
    <div class="et-hero__pattern" aria-hidden="true"></div>
    <div class="et-hero__slider">
        @foreach($slides as $i => $slide)
            <div class="et-hero__slide {{ $i === 0 ? 'is-active' : '' }}" data-hero-slide>
                <div class="et-container et-hero__grid">
                    <div class="et-hero__copy">
                        @if(!empty($slide['badge']))
                            <span class="et-hero__badge">{{ $slide['badge'] }}</span>
                        @endif
                        <h1 class="et-hero__title">{{ $slide['title'] }}</h1>
                        <p class="et-hero__desc">{{ $slide['description'] }}</p>
                        <div class="et-hero__actions">
                            <a href="{{ $slide['cta_url'] }}" class="et-btn et-btn--primary">{{ $slide['cta_label'] }}</a>
                            @if(!empty($slide['secondary_url']))
                                <a href="{{ $slide['secondary_url'] }}" class="et-btn et-btn--ghost">{{ $slide['secondary_label'] }}</a>
                            @endif
                        </div>
                        @if(!empty($slide['show_search']))
                            <form class="et-hero__search" action="{{ route('frontend.search') }}" method="get">
                                <input type="search" name="q" placeholder="Search exams, topics, news…" aria-label="Search">
                                <button type="submit" class="et-btn et-btn--primary et-btn--sm">Search</button>
                            </form>
                        @endif
                    </div>
                    <div class="et-hero__media">
                        <img
                            class="et-hero__illustration"
                            src="{{ $slide['illustration'] }}"
                            alt=""
                            loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
                        >
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="et-hero__dots" role="tablist" aria-label="Hero slides">
        @foreach($slides as $i => $slide)
            <button
                type="button"
                class="et-hero__dot {{ $i === 0 ? 'is-active' : '' }}"
                data-hero-dot
                aria-label="Go to slide {{ $i + 1 }}"
                aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
            ></button>
        @endforeach
    </div>
</section>
