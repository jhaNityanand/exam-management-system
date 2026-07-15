@php
    $newsletter = $page['newsletter'] ?? [];
    $title = $section->title ?? ($newsletter['title'] ?? '');
    $subtitle = $section->subtitle ?? ($newsletter['subtitle'] ?? '');
    $cta = $newsletter['cta'] ?? ($siteSettings['newsletter.cta'] ?? 'Subscribe');
@endphp
<section class="et-section">
    <div class="et-container">
        <div class="et-newsletter-band">
            @if($title !== '')
                <h2>{{ $title }}</h2>
            @endif
            @if($subtitle !== '')
                <p>{{ $subtitle }}</p>
            @endif
            @include('frontend.partials.newsletter-form', [
                'cta' => $cta,
                'source' => 'home',
            ])
        </div>
    </div>
</section>
