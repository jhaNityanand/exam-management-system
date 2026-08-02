{{-- Temporarily hidden: "Ready to learn and practice?" CTA band
@php
    $cta = $page['cta'] ?? [];
@endphp
<section class="et-section et-section--flush-top">
    <div class="et-container">
        @include('frontend.components.cta-band', [
            'title' => $cta['title'] ?? 'Ready to start your next exam?',
            'subtitle' => $cta['subtitle'] ?? 'Practice with structured mock tests, track scores, and learn with blogs & news built for aspirants.',
            'primaryLabel' => $cta['primary_label'] ?? 'Browse Exams',
            'primaryUrl' => $cta['primary_url'] ?? route('frontend.exams.index'),
            'secondaryLabel' => $cta['secondary_label'] ?? 'Create free account',
            'secondaryUrl' => $cta['secondary_url'] ?? route('register'),
        ])
    </div>
</section>
--}}
