<section class="et-section">
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => filled($section?->title) ? $section->title : 'Why aspirants choose Examtube',
            'subtitle' => filled($section?->subtitle) ? $section->subtitle : 'Live numbers from our learning platform.',
        ])
        @include('frontend.components.stats-grid', [
            'stats' => $page['stats'] ?? [],
        ])
    </div>
</section>
