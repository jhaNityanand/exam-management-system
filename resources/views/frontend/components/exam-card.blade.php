@php
    $examUrl = route('frontend.exams.show', $exam->slug ?? $exam);
    $difficulty = $exam->difficulty_level ?? null;
    $duration = $exam->duration ?? null;
    $questions = $exam->total_questions ?? null;
    $amount = $exam->exam_amount ?? null;
    $isFree = ($exam->pricing_option ?? 'free') === 'free' || (float) ($amount ?? 0) <= 0;
    $thumb = method_exists($exam, 'bannerUrl') ? $exam->bannerUrl() : ($exam->bannerImage->file_url ?? null);
@endphp
<article class="et-card et-exam-card">
    <a href="{{ $examUrl }}" class="et-card__media" tabindex="-1" aria-hidden="true">
        @if($thumb)
            <img src="{{ $thumb }}" alt="" loading="lazy">
        @else
            <img src="{{ asset('frontend/images/exams.svg') }}" alt="" class="et-card__media-fallback" loading="lazy">
        @endif
    </a>
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
            <a href="{{ $examUrl }}" class="et-btn et-btn--primary et-btn--sm">Attempt</a>
        </div>
    </div>
</article>
