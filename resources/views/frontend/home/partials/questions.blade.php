@php $questions = $page['randomQuestions'] ?? collect(); @endphp
<section class="et-section" data-reveal>
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => 'Practice Questions',
            'subtitle' => 'A fresh set of questions every visit — build concepts one answer at a time.',
            'actionUrl' => route('frontend.questions.index'),
            'actionLabel' => 'Show All Questions',
        ])
        @if($questions->isEmpty())
            @include('frontend.partials.empty-state', [
                'title' => 'No questions yet',
                'message' => 'Questions will show up here once published.',
            ])
        @else
            <div class="et-grid et-grid--3">
                @foreach($questions as $question)
                    @include('frontend.components.question-card', ['question' => $question])
                @endforeach
            </div>
            <div class="et-section__cta">
                <a href="{{ route('frontend.questions.index') }}" class="et-btn et-btn--primary">Show All Questions</a>
            </div>
        @endif
    </div>
</section>
