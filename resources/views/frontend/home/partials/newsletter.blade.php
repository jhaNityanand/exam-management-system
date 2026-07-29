<section class="et-section et-newsletter-section" data-reveal>
    <div class="et-container">
        <div class="et-newsletter-band et-newsletter-band--modern">
            <div class="et-newsletter-band__copy">
                <p class="et-eyebrow">Newsletter</p>
                <h2>Stay Exam-Ready Every Week</h2>
                <p>Get curated practice tips, new exams, and career-ready updates — no spam, only useful prep.</p>
                <ul class="et-newsletter-benefits">
                    <li>Weekly exam alerts</li>
                    <li>Practice tips from mentors</li>
                    <li>New blogs &amp; news digests</li>
                </ul>
                @include('frontend.partials.newsletter-form', [
                    'cta' => 'Subscribe',
                    'source' => 'home',
                ])
            </div>
            <div class="et-newsletter-band__art" aria-hidden="true">
                <img src="{{ asset('frontend/images/newsletter.svg') }}" alt="" loading="lazy">
            </div>
        </div>
    </div>
</section>
