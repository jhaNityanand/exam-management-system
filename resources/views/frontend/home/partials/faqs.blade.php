@php $faqs = $page['faqs'] ?? collect(); @endphp
<section class="et-section">
    <div class="et-container" style="max-width:820px;margin-inline:auto">
        @include('frontend.components.section-heading', [
            'title' => $section->title ?? '',
            'subtitle' => $section->subtitle ?? '',
        ])
        @if($faqs->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No FAQs yet', 'message' => ''])
        @else
            @include('frontend.components.faq-accordion', ['faqs' => $faqs])
        @endif
    </div>
</section>
