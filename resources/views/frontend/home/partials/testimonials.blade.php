@php $testimonials = $page['testimonials'] ?? collect(); @endphp
<section class="et-section et-section--alt">
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => $section->title ?? '',
            'subtitle' => $section->subtitle ?? '',
        ])
        @if($testimonials->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'Stories coming soon', 'message' => ''])
        @else
            <div class="et-grid et-grid--3">
                @foreach($testimonials as $testimonial)
                    @include('frontend.components.testimonial-card', ['testimonial' => $testimonial])
                @endforeach
            </div>
        @endif
    </div>
</section>
