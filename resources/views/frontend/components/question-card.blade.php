{{-- Question card --}}
<article class="et-card et-question-card">
    <div class="et-card__body">
        <div class="et-card__meta">
            @if($question->category)
                <a class="et-badge" href="{{ route('frontend.questions.category', $question->category->slug) }}">{{ $question->category->name }}</a>
            @endif
            @if($question->difficulty)
                <span class="et-badge et-badge--soft">{{ ucfirst($question->difficulty) }}</span>
            @endif
        </div>
        <h3 class="et-card__title">
            <a href="{{ route('frontend.questions.show', $question) }}">{{ $question->publicTitle() }}</a>
        </h3>
        <p class="et-card__excerpt">{{ \Illuminate\Support\Str::limit(strip_tags((string) $question->body), 140) }}</p>
        <div class="et-card__footer">
            <a class="et-link" href="{{ route('frontend.questions.show', $question) }}">Read explanation</a>
        </div>
    </div>
</article>
