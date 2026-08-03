{{-- Question card --}}
@php
    use Illuminate\Support\Str;

    $homeCompact = (bool) ($homeCompact ?? false);
    $questionUrl = route('frontend.questions.show', $question);
    $createdLabel = $question->created_at
        ? $question->created_at->timezone(config('app.timezone'))->format('d M Y')
        : null;

    $fullTitle = $question->publicTitle();
    $title = $homeCompact
        ? Str::limit($fullTitle, 72, '......')
        : $fullTitle;

    $explanationPlain = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($question->explanation ?? ''))));
    $bodyPlain = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($question->body ?? ''))));
    if ($homeCompact) {
        $excerpt = $explanationPlain !== ''
            ? Str::limit($explanationPlain, 120, '......')
            : ($bodyPlain !== '' && $bodyPlain !== $fullTitle
                ? Str::limit($bodyPlain, 120, '......')
                : null);
    } else {
        $excerpt = Str::limit($bodyPlain, 140);
    }
@endphp
<article class="et-card et-question-card{{ $homeCompact ? ' et-question-card--home' : '' }}">
    <div class="et-card__body">
        <div class="et-card__meta">
            @if($question->category)
                <a class="et-badge" href="{{ route('frontend.questions.category', $question->category->slug) }}">{{ $question->category->name }}</a>
            @endif
            @unless($homeCompact)
                @if($question->difficulty)
                    <span class="et-badge et-badge--soft">{{ ucfirst($question->difficulty) }}</span>
                @endif
            @endunless
        </div>
        <h3 class="et-card__title">
            <a href="{{ $questionUrl }}">{{ $title }}</a>
        </h3>
        @if(filled($excerpt))
            <p class="et-card__excerpt">{{ $excerpt }}</p>
        @endif
        @if($homeCompact)
            @if($createdLabel)
                <div class="et-question-card__meta-row">
                    <span class="et-question-card__date" title="Created date">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 3v4M16 3v4M3.5 10h17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        {{ $createdLabel }}
                    </span>
                </div>
            @endif
            <div class="et-card__footer et-question-card__footer--home">
                <a class="et-btn et-btn--soft et-btn--sm" href="{{ $questionUrl }}">View Details</a>
            </div>
        @else
            <div class="et-card__footer">
                <a class="et-btn et-btn--soft et-btn--sm" href="{{ $questionUrl }}">View Details</a>
            </div>
        @endif
    </div>
</article>
