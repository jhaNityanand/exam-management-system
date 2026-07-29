<section class="et-section">
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => 'Why aspirants choose Examtube',
            'subtitle' => 'Live numbers from our learning platform.',
        ])
        @include('frontend.components.stats-grid', [
            'stats' => $page['stats'] ?? [],
        ])
    </div>
</section>
