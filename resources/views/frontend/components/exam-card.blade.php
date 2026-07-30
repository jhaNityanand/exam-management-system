@php
    use Illuminate\Support\Str;

    $examUrl = route('frontend.exams.show', $exam->slug ?? $exam);
    $difficulty = $exam->difficulty_level ?? null;
    $duration = $exam->duration ?? null;
    $questions = $exam->total_questions ?? null;
    $marks = $exam->total_marks ?? null;
    $amount = $exam->exam_amount ?? null;
    $mode = $exam->exam_mode ?? null;
    $isFree = ($exam->pricing_option ?? 'free') === 'free' || (float) ($amount ?? 0) <= 0;
    $thumb = method_exists($exam, 'bannerUrl') ? $exam->bannerUrl() : ($exam->bannerImage->file_url ?? null);
    $excerpt = Str::limit(strip_tags((string) ($exam->description ?? '')), 110);
    $modeLabel = $mode ? ucfirst(str_replace('_', ' ', (string) $mode)) : null;
@endphp
<article class="et-card et-exam-card" data-reveal-card>
    <a href="{{ $examUrl }}" class="et-card__media" tabindex="-1" aria-hidden="true">
        @if($thumb)
            <img src="{{ $thumb }}" alt="" loading="lazy">
        @else
            <img src="{{ asset('frontend/images/exams.svg') }}" alt="" class="et-card__media-fallback" loading="lazy">
        @endif
        <span class="et-exam-card__price {{ $isFree ? 'is-free' : 'is-paid' }}">{{ $isFree ? 'Free' : 'Paid' }}</span>
    </a>
    <div class="et-card__body">
        <div class="et-card__meta">
            @if($exam->category)
                <span class="et-badge et-badge--soft">{{ $exam->category->name }}</span>
            @endif
            @if($difficulty)
                <span class="et-badge et-badge--slate">{{ ucfirst($difficulty) }}</span>
            @endif
            @if($modeLabel)
                <span class="et-badge et-badge--slate">{{ $modeLabel }}</span>
            @endif
        </div>

        <h3 class="et-card__title"><a href="{{ $examUrl }}">{{ $exam->title }}</a></h3>

        @if($excerpt !== '')
            <p class="et-exam-card__excerpt">{{ $excerpt }}</p>
        @endif

        <div class="et-exam-card__stats">
            @if($duration)
                <span title="Duration">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 8v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    {{ (int) $duration }} min
                </span>
            @endif
            @if($questions)
                <span title="Questions">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 7h8M8 12h8M8 17h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><rect x="4" y="4" width="16" height="16" rx="3" stroke="currentColor" stroke-width="2"/></svg>
                    {{ (int) $questions }} Qs
                </span>
            @endif
            @if($marks)
                <span title="Total marks">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3l2.2 6.6H21l-5.4 4 2.1 6.4L12 16.8 6.3 20l2.1-6.4L3 9.6h6.8L12 3z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    {{ (int) $marks }} marks
                </span>
            @endif
        </div>

        <div class="et-card__footer et-exam-card__actions">
            <a href="{{ $examUrl }}" class="et-btn et-btn--primary et-btn--sm">Attempt</a>
            <a href="{{ $examUrl }}" class="et-btn et-btn--ghost et-btn--sm">View details</a>
        </div>
    </div>
</article>
