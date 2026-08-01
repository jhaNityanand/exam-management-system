@php
    $brandName = 'Examtube.in';
    $logoSrc = asset('images/brand/logo.svg');
    $year = (string) now()->year;
    $appVersion = config('app.version', '1.0.0');

    $contactEmail = $siteSettings['contact.email'] ?? null;
    $contactPhone = $siteSettings['contact.phone'] ?? null;
    $contactAddress = $siteSettings['contact.address'] ?? null;
    $hasContact = $contactEmail || $contactPhone || $contactAddress;

    $usefulLinks = [
        ['label' => 'About Us', 'url' => url('/about-us')],
        ['label' => 'Contact Us', 'url' => url('/contact-us')],
        ['label' => 'FAQs', 'url' => route('frontend.faqs.index')],
        ['label' => 'Categories', 'url' => route('frontend.categories.index')],
        ['label' => 'Authors', 'url' => route('frontend.authors.index')],
        ['label' => 'Privacy Policy', 'url' => url('/privacy-policy')],
        ['label' => 'Terms & Conditions', 'url' => url('/terms-and-conditions')],
        ['label' => 'Sitemap', 'url' => route('frontend.sitemap')],
    ];
@endphp
<footer class="et-footer">
    <div class="et-container">
        <div class="et-footer__grid">
            <div class="et-footer__brand">
                <a href="{{ route('home') }}" class="et-logo" aria-label="{{ $brandName }}">
                    <img class="et-logo__img" src="{{ $logoSrc }}" alt="{{ $brandName }}" width="150" height="32" loading="lazy">
                </a>
                <p class="et-footer__desc">
                    Practice with structured exams, stay updated with education news, and learn from practical blogs — built for students, mentors, and institutes.
                </p>
                @if(($socialLinks ?? collect())->isNotEmpty())
                    <div class="et-social" aria-label="Social links">
                        @foreach($socialLinks as $link)
                            <a href="{{ $link->url }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               title="{{ $link->label }}"
                               class="et-social__link et-social__link--{{ $link->platform }}">
                                <span class="et-visually-hidden">{{ $link->label }}</span>
                                @include('backend.partials.social-platform-icon', ['platform' => $link->platform, 'size' => 16])
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="et-footer__col">
                <h3 class="et-footer__heading">Useful Links</h3>
                <div class="et-footer__links">
                    @foreach($usefulLinks as $item)
                        <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                    @endforeach
                </div>
            </div>

            <div class="et-footer__col">
                <h3 class="et-footer__heading">Contact</h3>
                @if($hasContact)
                    <ul class="et-footer__contact">
                        @if($contactEmail)
                            <li>
                                <span class="et-footer__contact-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16v12H4z"/><path d="M4 7l8 6 8-6"/></svg>
                                </span>
                                <div>
                                    <span class="et-footer__contact-label">Email</span>
                                    <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                                </div>
                            </li>
                        @endif
                        @if($contactPhone)
                            <li>
                                <span class="et-footer__contact-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v2a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6 19.8 19.8 0 01-3.1-8.7A2 2 0 014.1 2h2a2 2 0 012 1.7c.1.9.3 1.8.6 2.6a2 2 0 01-.5 2.1L7.1 9.9a16 16 0 006 6l1.5-1.1a2 2 0 012.1-.4c.8.3 1.7.5 2.6.6a2 2 0 011.7 2z"/></svg>
                                </span>
                                <div>
                                    <span class="et-footer__contact-label">Phone</span>
                                    <a href="tel:{{ preg_replace('/\s+/', '', $contactPhone) }}">{{ $contactPhone }}</a>
                                </div>
                            </li>
                        @endif
                        @if($contactAddress)
                            <li>
                                <span class="et-footer__contact-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-4.5 7-11a7 7 0 10-14 0c0 6.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                                </span>
                                <div>
                                    <span class="et-footer__contact-label">Address</span>
                                    <span>{{ $contactAddress }}</span>
                                </div>
                            </li>
                        @endif
                    </ul>
                @else
                    <p class="et-footer__muted">Reach us anytime via the contact page.</p>
                    <a href="{{ url('/contact-us') }}" class="et-footer__text-link">Contact Us →</a>
                @endif
            </div>

            <div class="et-footer__col et-footer__newsletter">
                <h3 class="et-footer__heading">Stay Exam-Ready</h3>
                <p class="et-footer__muted">Weekly alerts, practice tips, and prep updates — no spam.</p>
                @include('frontend.partials.newsletter-form', [
                    'cta' => 'Subscribe',
                    'compact' => true,
                    'source' => 'footer',
                ])
            </div>
        </div>

        <div class="et-footer__bottom">
            <div class="et-footer__copy">
                <p>&copy; {{ $year }} {{ $brandName }}. All Rights Reserved.</p>
                <p>Designed &amp; Developed by {{ $brandName }} &middot; v{{ $appVersion }}</p>
            </div>
            <div class="et-footer__legal">
                <a href="{{ url('/privacy-policy') }}">Privacy</a>
                <a href="{{ url('/terms-and-conditions') }}">Terms</a>
                <a href="{{ route('frontend.sitemap') }}">Sitemap</a>
            </div>
        </div>
    </div>
</footer>

<button
    type="button"
    class="et-back-top"
    data-back-top
    aria-label="Scroll to top"
    title="Back to top"
    hidden
>
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
</button>
