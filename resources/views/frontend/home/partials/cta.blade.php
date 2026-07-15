@php
    $cta = $page['cta'] ?? [];
    $title = $section->title ?? ($cta['title'] ?? '');
    $subtitle = $section->subtitle ?? ($cta['subtitle'] ?? '');
@endphp
<section class="et-section" style="padding-top:0">
    <div class="et-container">
        @include('frontend.components.cta-band', [
            'title' => $title,
            'subtitle' => $subtitle,
            'primaryLabel' => $cta['primary_label'] ?? null,
            'primaryUrl' => $cta['primary_url'] ?? null,
            'secondaryLabel' => $cta['secondary_label'] ?? null,
            'secondaryUrl' => $cta['secondary_url'] ?? null,
        ])
    </div>
</section>
