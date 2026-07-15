@php
    $avatar = $testimonial->avatar->file_url ?? null;
    $initial = strtoupper(mb_substr($testimonial->name ?? 'A', 0, 1));
    $rating = max(0, min(5, (int) ($testimonial->rating ?? 5)));
@endphp
<blockquote class="et-testimonial">
    <div class="et-testimonial__rating" aria-label="{{ $rating }} out of 5 stars">
        {{ str_repeat('★', $rating) }}{{ str_repeat('☆', 5 - $rating) }}
    </div>
    <p class="et-testimonial__quote">“{{ $testimonial->quote }}”</p>
    <footer class="et-testimonial__person">
        <div class="et-testimonial__avatar">
            @if($avatar)
                <img src="{{ $avatar }}" alt="">
            @else
                {{ $initial }}
            @endif
        </div>
        <div>
            <div class="et-testimonial__name">{{ $testimonial->name }}</div>
            <div class="et-testimonial__role">
                {{ collect([$testimonial->role ?? null, $testimonial->company ?? null])->filter()->implode(' · ') }}
            </div>
        </div>
    </footer>
</blockquote>
