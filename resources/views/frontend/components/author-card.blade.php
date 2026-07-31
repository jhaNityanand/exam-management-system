@php
    $avatar = user_avatar($author);
    $bio = \Illuminate\Support\Str::limit(
        $author->profile?->bio ?? 'Sharing practice insights, guides, and learning updates on Examtube.',
        110
    );
@endphp
<a href="{{ route('frontend.authors.show', $author->slug) }}" class="et-author-card">
    <div class="et-author-card__glow" aria-hidden="true"></div>
    <div class="et-author-card__media" style="--ua-bg: {{ $avatar['color'] }}">
        @if($avatar['url'])
            <img src="{{ $avatar['url'] }}" alt="" loading="lazy" width="96" height="96">
        @else
            <span class="et-author-card__initials" aria-hidden="true">{{ $avatar['initials'] }}</span>
        @endif
    </div>
    <div class="et-author-card__body">
        <h3 class="et-author-card__name">{{ $author->name }}</h3>
        <p class="et-author-card__bio">{{ $bio }}</p>
        <span class="et-author-card__cta">
            View profile
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
    </div>
</a>
