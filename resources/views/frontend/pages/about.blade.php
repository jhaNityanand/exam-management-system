@php
    $brand = $siteBrand['name'] ?? 'Examtube.in';
    $offers = [
        ['title' => 'Online Exams', 'text' => 'Timed mocks, scoring rules, and exam-day workflows for serious practice.', 'icon' => 'exam'],
        ['title' => 'Practice Questions', 'text' => 'Browse public questions with explanations and category-wise discovery.', 'icon' => 'questions'],
        ['title' => 'Educational Blogs', 'text' => 'Mentor-written guides for strategy, syllabus clarity, and study planning.', 'icon' => 'blogs'],
        ['title' => 'News & Updates', 'text' => 'Breaking alerts and trending education news for candidates and campuses.', 'icon' => 'news'],
        ['title' => 'Learning Resources', 'text' => 'Structured learning paths across exams, articles, and topic collections.', 'icon' => 'resources'],
        ['title' => 'Study Materials', 'text' => 'Category-based content that helps you revise with focus, not noise.', 'icon' => 'materials'],
        ['title' => 'Career Preparation', 'text' => 'Interview-ready practice, aptitude rounds, and role-focused assessments.', 'icon' => 'career'],
        ['title' => 'Knowledge Sharing', 'text' => 'Authors, institutes, and mentors publishing insights for every learner.', 'icon' => 'share'],
    ];
    $why = [
        ['title' => 'Exam-ready workflows', 'text' => 'Timers, shuffle rules, scoring, and attempt tracking designed like real papers.'],
        ['title' => 'More than tests', 'text' => 'Questions, blogs, news, and categories sit together as one learning platform.'],
        ['title' => 'Clear content hierarchy', 'text' => 'Find streams, topics, and authors quickly with searchable filters and catalogs.'],
        ['title' => 'Built for institutes', 'text' => 'Organization workspaces help teams publish exams and media with confidence.'],
        ['title' => 'Transparent progress', 'text' => 'Attempt history and structured feedback help learners improve week by week.'],
        ['title' => 'Trust-first experience', 'text' => 'Privacy-aware accounts, published content controls, and support pathways.'],
    ];
    $values = [
        ['title' => 'Clarity', 'text' => 'Every exam, article, and update should reduce confusion — not add to it.'],
        ['title' => 'Integrity', 'text' => 'Honest practice environments that respect exam rules and learner trust.'],
        ['title' => 'Accessibility', 'text' => 'Useful preparation should be approachable for students and institutes alike.'],
        ['title' => 'Continuous improvement', 'text' => 'We refine workflows so practice feels closer to the real assessment floor.'],
    ];
    $faqs = [
        ['q' => 'Is Examtube only an exam platform?', 'a' => 'No. Examtube is a complete learning and knowledge platform that combines online exams, practice questions, educational blogs, news updates, study materials, and category-based discovery.'],
        ['q' => 'Who is Examtube built for?', 'a' => 'Students preparing for competitive and academic exams, mentors creating guidance content, and institutes that need structured exam administration.'],
        ['q' => 'Can I practice without joining an institute?', 'a' => 'Yes. Public catalogs of exams, questions, blogs, and news are available for independent learners, while institutes can also run private workspaces.'],
        ['q' => 'How do I get support?', 'a' => 'Visit the FAQs page or contact our support team through the Contact Us form. We respond during published support hours.'],
    ];
@endphp

<section class="et-sp-hero et-sp-hero--about">
    <div class="et-container">
        @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'About Us'],
        ]])
        <div class="et-sp-hero__grid">
            <div class="et-sp-hero__copy">
                <p class="et-eyebrow">{{ $eyebrow }}</p>
                <h1>A complete learning platform for exams, knowledge, and career prep</h1>
                <p class="et-sp-hero__lead">
                    {{ $brand }} is more than an online exam management system. It is a unified space for practice papers,
                    questions, blogs, news, study materials, and knowledge sharing — built for students, mentors, and institutes.
                </p>
                <div class="et-sp-hero__actions">
                    <a href="{{ route('frontend.exams.index') }}" class="et-btn et-btn--primary">Explore exams</a>
                    <a href="{{ route('frontend.questions.index') }}" class="et-btn et-btn--soft">Browse questions</a>
                </div>
            </div>
            <div class="et-sp-hero__visual" aria-hidden="true">
                <div class="et-sp-orb et-sp-orb--1"></div>
                <div class="et-sp-orb et-sp-orb--2"></div>
                <div class="et-sp-hero-card">
                    <span>Online Exams</span>
                    <span>Practice Questions</span>
                    <span>Blogs & News</span>
                    <span>Study Resources</span>
                    <span>Career Prep</span>
                    <span>Knowledge Sharing</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="et-sp-section">
    <div class="et-container et-sp-stats">
        <article class="et-sp-stat">
            <strong>9+</strong>
            <span>Learning surfaces in one product</span>
        </article>
        <article class="et-sp-stat">
            <strong>1</strong>
            <span>Unified catalog for exams & knowledge</span>
        </article>
        <article class="et-sp-stat">
            <strong>24/7</strong>
            <span>Self-paced practice availability</span>
        </article>
        <article class="et-sp-stat">
            <strong>Trust</strong>
            <span>Privacy-aware, publish-ready workflows</span>
        </article>
    </div>
</section>

<section class="et-sp-section">
    <div class="et-container et-sp-split">
        <div>
            <p class="et-eyebrow">Who we are</p>
            <h2>Examtube brings exams and knowledge into one focused experience</h2>
        </div>
        <div class="et-sp-prose">
            <p>
                We built {{ $brand }} for learners who need more than a question dump or a single mock paper.
                Preparation today spans timed exams, topic practice, mentor guidance, news awareness, and career readiness —
                and those pieces should feel connected.
            </p>
            <p>
                Our platform helps aspirants attempt exams with confidence, helps authors publish useful guidance,
                and helps institutes run structured assessments without losing the learning context around every paper.
            </p>
        </div>
    </div>
</section>

<section class="et-sp-section et-sp-section--soft">
    <div class="et-container">
        <div class="et-sp-mv">
            <article class="et-sp-mv__card">
                <p class="et-eyebrow">Our mission</p>
                <h2>Help every learner prepare with clarity and confidence</h2>
                <p>
                    We exist to make exam practice honest, structured, and useful — while surrounding every attempt with
                    questions, insights, and updates that improve decision-making before and after the paper.
                </p>
            </article>
            <article class="et-sp-mv__card et-sp-mv__card--accent">
                <p class="et-eyebrow">Our vision</p>
                <h2>Become the trusted knowledge hub for exam-ready careers</h2>
                <p>
                    We envision a platform where online exams, study materials, educational blogs, and career preparation
                    work together — so students and institutes grow through shared knowledge, not fragmented tools.
                </p>
            </article>
        </div>
    </div>
</section>

<section class="et-sp-section" id="what-we-offer">
    <div class="et-container">
        <div class="et-sp-section__head">
            <p class="et-eyebrow">What we offer</p>
            <h2>A full learning stack — not just exam software</h2>
            <p>From timed mocks to blogs, news, and category-based study paths, Examtube covers the journey around every assessment.</p>
        </div>
        <div class="et-sp-feature-grid">
            @foreach ($offers as $item)
                <article class="et-sp-feature">
                    <span class="et-sp-feature__icon" aria-hidden="true">
                        @include('frontend.pages.partials.offer-icon', ['icon' => $item['icon']])
                    </span>
                    <h3>{{ $item['title'] }}</h3>
                    <p>{{ $item['text'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="et-sp-section et-sp-section--soft">
    <div class="et-container">
        <div class="et-sp-section__head">
            <p class="et-eyebrow">Why choose Examtube</p>
            <h2>Designed for serious practice and lasting understanding</h2>
        </div>
        <div class="et-sp-why-grid">
            @foreach ($why as $item)
                <article class="et-sp-why">
                    <h3>{{ $item['title'] }}</h3>
                    <p>{{ $item['text'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="et-sp-section">
    <div class="et-container et-sp-help">
        <div class="et-sp-section__head et-sp-section__head--left">
            <p class="et-eyebrow">How we help students</p>
            <h2>From first practice attempt to career-ready confidence</h2>
        </div>
        <ol class="et-sp-timeline">
            <li>
                <strong>Discover</strong>
                <span>Browse exams, questions, blogs, and news by category or topic.</span>
            </li>
            <li>
                <strong>Practice</strong>
                <span>Attempt timed papers and review explanations with focused follow-up questions.</span>
            </li>
            <li>
                <strong>Learn</strong>
                <span>Use mentor blogs and study materials to close gaps between attempts.</span>
            </li>
            <li>
                <strong>Stay current</strong>
                <span>Follow education news and platform updates that affect your preparation plan.</span>
            </li>
            <li>
                <strong>Progress</strong>
                <span>Build consistency through structured catalogs, authors, and institute pathways.</span>
            </li>
        </ol>
    </div>
</section>

<section class="et-sp-section et-sp-section--soft">
    <div class="et-container">
        <div class="et-sp-section__head">
            <p class="et-eyebrow">Our values</p>
            <h2>Principles that shape every product decision</h2>
        </div>
        <div class="et-sp-values">
            @foreach ($values as $item)
                <article class="et-sp-value">
                    <h3>{{ $item['title'] }}</h3>
                    <p>{{ $item['text'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="et-sp-section">
    <div class="et-container et-sp-faq-wrap">
        <div class="et-sp-section__head et-sp-section__head--left">
            <p class="et-eyebrow">FAQ</p>
            <h2>Quick answers about the platform</h2>
        </div>
        <div class="et-faq" data-faq>
            @foreach ($faqs as $i => $faq)
                @php $panelId = 'about-faq-panel-'.$i; @endphp
                <div class="et-faq__item" data-faq-item>
                    <button type="button" class="et-faq__trigger" data-faq-trigger aria-expanded="false" aria-controls="{{ $panelId }}" id="about-faq-{{ $i }}">
                        <span>{{ $faq['q'] }}</span>
                        <span class="et-faq__icon" aria-hidden="true">+</span>
                    </button>
                    <div class="et-faq__panel" id="{{ $panelId }}" role="region" aria-labelledby="about-faq-{{ $i }}">{{ $faq['a'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="et-sp-cta">
    <div class="et-container et-sp-cta__inner">
        <div>
            <h2>Start learning with Examtube today</h2>
            <p>Explore exams, practice questions, blogs, and news — or talk to us about institute onboarding.</p>
        </div>
        <div class="et-sp-cta__actions">
            <a href="{{ route('frontend.exams.index') }}" class="et-btn et-btn--primary">Browse exams</a>
            <a href="{{ url('/contact-us') }}" class="et-btn et-btn--soft">Contact us</a>
        </div>
    </div>
</section>
