@php
    $examUrl = Route::has('frontend.exams.show')
        ? route('frontend.exams.show', $exam->slug ?? $exam)
        : '#';
    $difficulty = $exam->difficulty_level ?? null;
    $duration = $exam->duration ?? null;
    $questions = $exam->total_questions ?? null;
    $amount = $exam->exam_amount ?? null;
    $isFree = ($exam->pricing_option ?? 'free') === 'free' || (float) ($amount ?? 0) <= 0;
@endphp
<article class="et-card et-exam-card">
    <div class="et-card__body">
        <div class="et-card__meta">
            @if($exam->category)
                <span class="et-badge">{{ $exam->category->name }}</span>
            @endif
            @if($difficulty)
                <span class="et-badge et-badge--slate">{{ ucfirst($difficulty) }}</span>
            @endif
            <span class="et-badge {{ $isFree ? 'et-badge' : 'et-badge--warn' }}">{{ $isFree ? 'Free' : 'Paid' }}</span>
        </div>
        <h3 class="et-card__title"><a href="{{ $examUrl }}">{{ $exam->title }}</a></h3>
        @if($exam->description)
            <p class="et-card__excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($exam->description), 120) }}</p>
        @endif
        <div class="et-exam-card__stats">
            @if($duration)
                <span>{{ (int) $duration }} min</span>
            @endif
            @if($questions)
                <span>{{ (int) $questions }} Qs</span>
            @endif
            @if($exam->total_marks)
                <span>{{ (int) $exam->total_marks }} marks</span>
            @endif
        </div>
        <div class="et-card__footer">
            <a href="{{ $examUrl }}" class="et-btn et-btn--soft et-btn--sm">View exam</a>
            @if($exam->scheduled_start && $exam->scheduled_start->isFuture())
                <span class="et-card__meta">{{ $exam->scheduled_start->format('d M Y') }}</span>
            @endif
        </div>
    </div>
</article>
