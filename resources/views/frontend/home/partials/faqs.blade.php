@php $faqs = $page['faqs'] ?? collect(); @endphp
<section class="et-section">
    <div class="et-container et-container--narrow">
        @include('frontend.components.section-heading', [
            'title' => filled($section?->title) ? $section->title : 'Frequently asked questions',
            'subtitle' => filled($section?->subtitle) ? $section->subtitle : 'Quick answers about exams, accounts, and preparation on Examtube.',
        ])
        @if($faqs->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No FAQs yet', 'message' => ''])
        @else
            @include('frontend.components.faq-accordion', ['faqs' => $faqs])
        @endif
    </div>
</section>
