@php $exams = $page['featuredExams'] ?? collect(); @endphp
<section class="et-section et-section--alt">
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => $section->title ?? '',
            'subtitle' => $section->subtitle ?? '',
            'actionUrl' => Route::has('frontend.exams.index') ? route('frontend.exams.index') : null,
            'actionLabel' => ($section->settings['action_label'] ?? null) ?: 'View all',
        ])
        @if($exams->isEmpty())
            @include('frontend.partials.empty-state', [
                'title' => 'No featured exams yet',
                'message' => 'Published exams will appear here.',
                'actionUrl' => Route::has('frontend.exams.index') ? route('frontend.exams.index') : null,
                'actionLabel' => 'Browse exams',
            ])
        @else
            <div class="et-grid et-grid--4">
                @foreach($exams as $exam)
                    @include('frontend.components.exam-card', ['exam' => $exam])
                @endforeach
            </div>
        @endif
    </div>
</section>
