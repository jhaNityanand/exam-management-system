@php
    $avatar = user_avatar($author);
@endphp
<a href="{{ route('frontend.authors.show', $author->slug) }}" class="et-card et-author-card">
    <div class="et-card__body" style="display:flex;align-items:center;gap:.9rem">
        <span class="et-profile__avatar" style="--ua-bg: {{ $avatar['color'] }};width:3rem;height:3rem;font-size:.9rem">
            @if($avatar['url'])
                <img src="{{ $avatar['url'] }}" alt="" loading="lazy">
            @else
                {{ $avatar['initials'] }}
            @endif
        </span>
        <span>
            <strong style="display:block">{{ $author->name }}</strong>
            <span class="et-card__meta">{{ \Illuminate\Support\Str::limit($author->profile?->bio ?? 'Examtube contributor', 70) }}</span>
        </span>
    </div>
</a>
