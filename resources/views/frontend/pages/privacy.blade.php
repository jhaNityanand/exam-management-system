@php
    $brand = $siteBrand['name'] ?? 'Examtube.in';
    $sections = [
        [
            'id' => 'collect',
            'title' => 'Information We Collect',
            'body' => '<p>We collect information you provide directly and information generated while using '.$brand.', including:</p><ul><li><strong>Account details</strong> such as name, email address, and profile information.</li><li><strong>Learning activity</strong> such as exam attempts, scores, progress, and saved preferences.</li><li><strong>Communications</strong> you send through contact forms, support requests, or newsletter subscriptions.</li><li><strong>Technical data</strong> such as browser type, device information, IP address, and approximate usage logs needed for security and reliability.</li></ul>',
        ],
        [
            'id' => 'use',
            'title' => 'How We Use Information',
            'body' => '<p>We use personal information to:</p><ul><li>Create and manage accounts.</li><li>Deliver exams, questions, blogs, news, and related learning features.</li><li>Personalize catalogs, reminders, and support responses.</li><li>Improve platform performance, integrity, and content quality.</li><li>Send service notices and, where permitted, product updates.</li><li>Detect abuse, prevent fraud, and protect exam integrity.</li></ul>',
        ],
        [
            'id' => 'cookies',
            'title' => 'Cookies',
            'body' => '<p>'.$brand.' uses cookies and similar technologies to keep you signed in, remember preferences (including theme), measure essential usage, and improve reliability.</p><p>You can control cookies through your browser settings. Disabling some cookies may limit authenticated features or preference persistence.</p>',
        ],
        [
            'id' => 'third-party',
            'title' => 'Third-Party Services',
            'body' => '<p>We may use trusted service providers for hosting, email delivery, analytics, security, payment processing (when enabled), or spam protection.</p><p>These providers process data only as needed to deliver their services and are expected to protect information in line with applicable agreements and laws.</p>',
        ],
        [
            'id' => 'security',
            'title' => 'Data Security',
            'body' => '<p>We apply administrative, technical, and organizational safeguards designed to protect account and learning data against unauthorized access, alteration, disclosure, or destruction.</p><p>No online service can guarantee absolute security. Please keep credentials confidential and notify us promptly if you suspect unauthorized account activity.</p>',
        ],
        [
            'id' => 'rights',
            'title' => 'User Rights',
            'body' => '<p>Depending on your location and applicable law, you may request access to, correction of, or deletion of personal information we hold about you. You may also ask us to restrict certain processing or withdraw consent where processing is consent-based.</p><p>To exercise these rights, contact us using the details below. We may need to verify your identity before fulfilling a request.</p>',
        ],
        [
            'id' => 'retention',
            'title' => 'Data Retention',
            'body' => '<p>We retain personal information for as long as needed to provide the platform, meet legal obligations, resolve disputes, and maintain security records.</p><p>Exam attempts and learning history may be retained to preserve academic integrity and account continuity unless deletion is required or reasonably requested.</p>',
        ],
        [
            'id' => 'contact',
            'title' => 'Contact Information',
            'body' => '<p>For privacy questions or data requests related to '.$brand.', contact our support team through the <a href="'.url('/contact-us').'">Contact Us</a> page or email <a href="mailto:'.e($siteSettings['contact.email'] ?? 'hello@examtube.in').'">'.e($siteSettings['contact.email'] ?? 'hello@examtube.in').'</a>.</p>',
        ],
    ];
@endphp

<section class="et-sp-hero et-sp-hero--legal">
    <div class="et-container">
        @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Privacy Policy'],
        ]])
        <p class="et-eyebrow">{{ $eyebrow }}</p>
        <h1>Privacy Policy</h1>
        <p class="et-sp-hero__lead">
            How {{ $brand }} collects, uses, stores, and protects your information across exams, learning content, and account services.
        </p>
        <p class="et-sp-updated">Last updated: {{ now()->format('F j, Y') }}</p>
    </div>
</section>

<section class="et-sp-section">
    <div class="et-container et-legal-layout">
        <aside class="et-legal-toc" aria-label="Privacy sections">
            <p class="et-legal-toc__label">On this page</p>
            <ul>
                @foreach ($sections as $section)
                    <li><a href="#privacy-{{ $section['id'] }}" data-legal-nav>{{ $section['title'] }}</a></li>
                @endforeach
            </ul>
            <div class="et-legal-toc__card">
                <p>Questions about your data?</p>
                <a href="{{ url('/contact-us') }}" class="et-btn et-btn--primary et-btn--sm">Contact support</a>
            </div>
        </aside>

        <div class="et-legal-main" data-legal-accordion>
            <article class="et-legal-intro et-panel">
                <p>
                    Your trust matters. This Privacy Policy explains what information we collect when you use {{ $brand }},
                    why we collect it, and the choices available to you. By using the platform, you acknowledge this policy.
                </p>
            </article>

            @foreach ($sections as $index => $section)
                <section class="et-legal-card" id="privacy-{{ $section['id'] }}" data-legal-item @if($index === 0) data-open @endif>
                    <button type="button" class="et-legal-card__trigger" data-legal-trigger aria-expanded="{{ $index === 0 ? 'true' : 'false' }}">
                        <span class="et-legal-card__index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="et-legal-card__title">{{ $section['title'] }}</span>
                        <span class="et-legal-card__chevron" aria-hidden="true"></span>
                    </button>
                    <div class="et-legal-card__panel et-prose" @if($index !== 0) hidden @endif>
                        {!! $section['body'] !!}
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</section>

<section class="et-sp-cta et-sp-cta--compact">
    <div class="et-container et-sp-cta__inner">
        <div>
            <h2>Need the Terms as well?</h2>
            <p>Review platform rules, exam integrity expectations, and account responsibilities.</p>
        </div>
        <div class="et-sp-cta__actions">
            <a href="{{ url('/terms-and-conditions') }}" class="et-btn et-btn--primary">View Terms</a>
            <a href="{{ route('frontend.faqs.index') }}" class="et-btn et-btn--soft">Browse FAQs</a>
        </div>
    </div>
</section>
