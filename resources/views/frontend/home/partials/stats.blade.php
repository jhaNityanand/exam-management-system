<section class="et-section">
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => $section->title ?? '',
            'subtitle' => $section->subtitle ?? '',
        ])
        @include('frontend.components.stats-grid', [
            'stats' => $page['stats'] ?? [],
        ])
    </div>
</section>
