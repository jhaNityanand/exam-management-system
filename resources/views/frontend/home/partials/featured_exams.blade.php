@php $exams = $page['featuredExams'] ?? collect(); @endphp
<section class="et-section et-section--alt" data-reveal>
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => 'Practice Exams',
            'subtitle' => 'Latest and popular exams to sharpen your preparation.',
            'actionUrl' => route('frontend.exams.index'),
            'actionLabel' => 'Show All Exams',
        ])
        @if($exams->isEmpty())
            @include('frontend.partials.empty-state', [
                'title' => 'No exams yet',
                'message' => 'Published exams will appear here.',
                'actionUrl' => route('frontend.exams.index'),
                'actionLabel' => 'Browse exams',
            ])
        @else
            <div class="et-grid et-grid--4">
                @foreach($exams as $exam)
                    @include('frontend.components.exam-card', ['exam' => $exam])
                @endforeach
            </div>
            <div class="et-section__cta">
                <a href="{{ route('frontend.exams.index') }}" class="et-btn et-btn--primary">Show All Exams</a>
            </div>
        @endif
    </div>
</section>
