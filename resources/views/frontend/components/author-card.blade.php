@php
    $avatar = user_avatar($author);
    $role = author_role($author);
@endphp
<a href="{{ route('frontend.authors.show', $author->slug) }}" class="et-author-card">
    <div class="et-author-card__media" style="--ua-bg: {{ $avatar['color'] }}">
        @if($avatar['url'])
            <img src="{{ $avatar['url'] }}" alt="" loading="lazy" width="72" height="72">
        @else
            <span class="et-author-card__initials" aria-hidden="true">{{ $avatar['initials'] }}</span>
        @endif
    </div>
    <div class="et-author-card__body">
        <div class="et-author-card__topline">
            <h3 class="et-author-card__name">{{ $author->name }}</h3>
            <span class="et-author-card__role et-author-card__role--{{ $role['key'] }}">{{ $role['short'] }}</span>
        </div>
        <p class="et-author-card__bio">{{ \Illuminate\Support\Str::limit($author->profile?->bio ?? 'Mentor and content contributor on Examtube.', 96) }}</p>
        <span class="et-author-card__cta">View profile →</span>
    </div>
</a>
