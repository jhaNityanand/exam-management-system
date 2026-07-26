@props([
    'feedback',
])

@php
    $user = $feedback->user;
    $avatar = user_avatar($user);
    $when = $feedback->created_at;
@endphp

<article class="fb-card">
    <header class="fb-card__head">
        <x-user-avatar :user="$user" size="md" />
        <div class="fb-card__identity">
            <strong>{{ $avatar['name'] }}</strong>
            <div class="fb-card__stars" aria-label="{{ (int) $feedback->rating }} out of 5 stars">
                @for($i = 1; $i <= 5; $i++)
                    <span class="fb-card__star{{ $i <= (int) $feedback->rating ? ' is-on' : '' }}" aria-hidden="true">★</span>
                @endfor
            </div>
        </div>
        <time class="fb-card__time"
              datetime="{{ optional($when)?->toIso8601String() }}"
              title="{{ optional($when)?->timezone(config('app.timezone'))->format('d M Y, H:i') }}">
            {{ optional($when)?->diffForHumans() ?: '—' }}
        </time>
    </header>

    @if(filled($feedback->title))
        <h3 class="fb-card__title">{{ $feedback->title }}</h3>
    @endif

    <p class="fb-card__message">{{ $feedback->message }}</p>
</article>
