@php
    $brand = $siteBrand['name'] ?? 'Examtube.in';
    $sections = [
        [
            'id' => 'acceptance',
            'title' => 'Acceptance of Terms',
            'body' => '<p>By accessing or using '.$brand.', creating an account, attempting an exam, or consuming published content, you agree to these Terms &amp; Conditions and our Privacy Policy.</p><p>If you do not agree, do not use the platform.</p>',
        ],
        [
            'id' => 'responsibilities',
            'title' => 'User Responsibilities',
            'body' => '<p>You agree to use '.$brand.' lawfully and respectfully. You must not:</p><ul><li>Attempt to disrupt platform availability or security.</li><li>Share account credentials or impersonate another person.</li><li>Upload unlawful, harmful, or infringing content.</li><li>Misuse exams, questions, or media for unauthorized redistribution.</li></ul>',
        ],
        [
            'id' => 'registration',
            'title' => 'Account Registration',
            'body' => '<p>Some features require an account. You are responsible for providing accurate information and for all activity under your credentials.</p><p>Notify us promptly if you believe your account has been compromised. We may suspend accounts that violate these terms or threaten platform integrity.</p>',
        ],
        [
            'id' => 'exam-rules',
            'title' => 'Exam Rules',
            'body' => '<p>When attempting exams on '.$brand.', you agree to follow published exam settings and integrity expectations, including timers, attempt limits, and scoring rules.</p><ul><li>Do not cheat, share restricted exam content, or use unauthorized assistance.</li><li>Do not attempt to reverse engineer scoring or bypass proctoring controls where enabled.</li><li>Institutes may apply additional rules for private assessments.</li></ul>',
        ],
        [
            'id' => 'content',
            'title' => 'Content Usage',
            'body' => '<p>Blogs, news, questions, study materials, and other learning resources are provided for personal learning and authorized institutional use.</p><p>You may not scrape, republish, sell, or commercially exploit platform content without prior written permission, except where fair dealing or other legal exceptions clearly apply.</p>',
        ],
        [
            'id' => 'ip',
            'title' => 'Intellectual Property',
            'body' => '<p>The '.$brand.' name, interface, software, branding, and original platform materials are protected by intellectual property laws.</p><p>Authors and institutes retain rights in content they lawfully publish, subject to the licenses required for platform delivery and display.</p>',
        ],
        [
            'id' => 'payments',
            'title' => 'Payments',
            'body' => '<p>Some exams, packages, or institute services may be paid. Prices, access duration, and refund eligibility (if any) are shown at purchase time or in the relevant offer terms.</p><p>Failed, disputed, or fraudulent payments may result in suspended access until resolved.</p>',
        ],
        [
            'id' => 'liability',
            'title' => 'Limitation of Liability',
            'body' => '<p>'.$brand.' is provided on an “as available” basis. Learning outcomes depend on individual effort and circumstances.</p><p>To the fullest extent permitted by law, we are not liable for indirect, incidental, special, consequential, or punitive damages, or for loss of data, profits, or opportunities arising from platform use.</p>',
        ],
        [
            'id' => 'termination',
            'title' => 'Termination',
            'body' => '<p>You may stop using the platform at any time. We may suspend or terminate access if you violate these terms, threaten security or exam integrity, or if required for legal or operational reasons.</p><p>Provisions that by nature should survive termination (including intellectual property, liability limits, and governing terms) will continue to apply.</p>',
        ],
        [
            'id' => 'changes',
            'title' => 'Changes to Terms',
            'body' => '<p>We may update these Terms &amp; Conditions to reflect product, legal, or operational changes. Updated terms become effective when published on this page unless a later date is stated.</p><p>Continued use after changes constitutes acceptance of the revised terms.</p>',
        ],
        [
            'id' => 'contact',
            'title' => 'Contact Information',
            'body' => '<p>Questions about these terms can be sent through our <a href="'.url('/contact-us').'">Contact Us</a> page or emailed to <a href="mailto:'.e($siteSettings['contact.email'] ?? 'hello@examtube.in').'">'.e($siteSettings['contact.email'] ?? 'hello@examtube.in').'</a>.</p>',
        ],
    ];
@endphp

<section class="et-sp-hero et-sp-hero--legal">
    <div class="et-container">
        @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Terms & Conditions'],
        ]])
        <p class="et-eyebrow">{{ $eyebrow }}</p>
        <h1>Terms &amp; Conditions</h1>
        <p class="et-sp-hero__lead">
            The rules that govern accounts, exams, content usage, and fair use of {{ $brand }} as a learning and assessment platform.
        </p>
        <p class="et-sp-updated">Last updated: {{ now()->format('F j, Y') }}</p>
        @include('frontend.partials.ad-placement', ['page' => 'terms', 'position' => 'below_title'])
    </div>
</section>

<section class="et-sp-section">
    <div class="et-container et-legal-layout">
        <aside class="et-legal-toc" aria-label="Terms sections">
            <p class="et-legal-toc__label">On this page</p>
            <ul>
                @foreach ($sections as $section)
                    <li><a href="#terms-{{ $section['id'] }}" data-legal-nav>{{ $section['title'] }}</a></li>
                @endforeach
            </ul>
            <div class="et-legal-toc__card">
                <p>Read how we handle personal data.</p>
                <a href="{{ url('/privacy-policy') }}" class="et-btn et-btn--soft et-btn--sm">Privacy Policy</a>
            </div>
            @include('frontend.partials.ad-placement', ['page' => 'terms', 'position' => 'left_after_toc'])
            @include('frontend.partials.ad-placement', ['page' => 'terms', 'position' => 'left_after_privacy'])
        </aside>

        <div class="et-legal-main" data-legal-accordion>
            <article class="et-legal-intro et-panel">
                <p>
                    These Terms explain your responsibilities when using exams, questions, blogs, news, and other learning
                    features on {{ $brand }}. Please read them carefully before creating an account or starting an attempt.
                </p>
            </article>
            @include('frontend.partials.ad-placement', ['page' => 'terms', 'position' => 'before_content'])

            @foreach ($sections as $index => $section)
                <section class="et-legal-card" id="terms-{{ $section['id'] }}" data-legal-item @if($index === 0) data-open @endif>
                    <button type="button"
                            class="et-legal-card__trigger"
                            id="terms-trigger-{{ $section['id'] }}"
                            data-legal-trigger
                            aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                            aria-controls="terms-panel-{{ $section['id'] }}">
                        <span class="et-legal-card__index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="et-legal-card__title">{{ $section['title'] }}</span>
                        <span class="et-legal-card__chevron" aria-hidden="true"></span>
                    </button>
                    <div class="et-legal-card__panel et-prose"
                         id="terms-panel-{{ $section['id'] }}"
                         role="region"
                         aria-labelledby="terms-trigger-{{ $section['id'] }}"
                         @if($index !== 0) hidden @endif>
                        {!! $section['body'] !!}
                    </div>
                </section>
            @endforeach
            @include('frontend.partials.ad-placement', ['page' => 'terms', 'position' => 'after_legal_sections'])
        </div>
    </div>
</section>

<section class="et-sp-cta et-sp-cta--compact">
    <div class="et-container et-sp-cta__inner">
        <div>
            <h2>Ready to practice with clarity?</h2>
            <p>Explore exams and learning content, or contact support if you need onboarding help.</p>
        </div>
        <div class="et-sp-cta__actions">
            <a href="{{ route('frontend.exams.index') }}" class="et-btn et-btn--primary">Browse exams</a>
            <a href="{{ url('/contact-us') }}" class="et-btn et-btn--soft">Contact us</a>
        </div>
    </div>
</section>
