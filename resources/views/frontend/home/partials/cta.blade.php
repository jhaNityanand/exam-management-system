<section class="et-section" style="padding-top:0">
    <div class="et-container">
        @include('frontend.components.cta-band', [
            'title' => 'Ready to start your next exam?',
            'subtitle' => 'Practice with structured mock tests, track scores, and learn with blogs & news built for aspirants.',
            'primaryLabel' => 'Browse Exams',
            'primaryUrl' => route('frontend.exams.index'),
            'secondaryLabel' => 'Create free account',
            'secondaryUrl' => route('register'),
        ])
    </div>
</section>
