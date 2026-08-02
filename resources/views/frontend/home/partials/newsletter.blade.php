{{-- Temporarily hidden: "Stay ahead every week" newsletter band
@php
    $newsletter = $page['newsletter'] ?? [];
    $title = $newsletter['title'] ?? 'Stay Exam-Ready Every Week';
    $subtitle = $newsletter['subtitle'] ?? 'Get curated practice tips, new exams, and career-ready updates — no spam, only useful prep.';
    $cta = $newsletter['cta'] ?? 'Subscribe';
@endphp
<section class="et-section et-newsletter-section" data-reveal>
    <div class="et-container">
        <div class="et-newsletter-band et-newsletter-band--panel">
            <div class="et-newsletter-band__copy">
                <p class="et-eyebrow">Newsletter</p>
                <h2>{{ $title }}</h2>
                <p>{{ $subtitle }}</p>
                @include('frontend.partials.newsletter-form', [
                    'cta' => $cta,
                    'source' => 'home',
                ])
            </div>
            <div class="et-newsletter-band__art" aria-hidden="true">
                <img src="{{ asset('frontend/images/newsletter.svg') }}" alt="" loading="lazy" width="320" height="240">
            </div>
        </div>
    </div>
</section>
--}}
