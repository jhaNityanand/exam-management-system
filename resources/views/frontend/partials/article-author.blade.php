@php
    $authorUser = $authorUser ?? null;
    $authorName = trim((string) ($authorName ?? ($authorUser->name ?? '')));
    if ($authorName === '') {
        $authorName = $fallbackName ?? 'Examtube Editorial';
    }

    $avatar = function_exists('user_avatar')
        ? user_avatar($authorUser, $authorName)
        : ['url' => null, 'initials' => strtoupper(substr($authorName, 0, 1)), 'color' => '#0f766e'];

    $bio = trim((string) ($authorUser?->profile?->bio ?? ''));
    if ($bio === '') {
        $bio = $defaultBio ?? 'Sharing practice insights, guides, and learning updates on Examtube.';
    }

    $profileUrl = ($authorUser && ! empty($authorUser->slug) && \Illuminate\Support\Facades\Route::has('frontend.authors.show'))
        ? route('frontend.authors.show', $authorUser->slug)
        : null;

    $publishedLabel = $publishedLabel ?? null;
@endphp
<section class="et-article__author-box" aria-label="About the author">
    <div class="et-article__author-box-media" style="--ua-bg: {{ $avatar['color'] }}">
        @if(!empty($avatar['url']))
            <img src="{{ $avatar['url'] }}" alt="" loading="lazy" width="72" height="72">
        @else
            <span aria-hidden="true">{{ $avatar['initials'] }}</span>
        @endif
    </div>
    <div class="et-article__author-box-body">
        <p class="et-article__author-box-label">Written by</p>
        <h2 class="et-article__author-box-name">
            @if($profileUrl)
                <a href="{{ $profileUrl }}">{{ $authorName }}</a>
            @else
                {{ $authorName }}
            @endif
        </h2>
        <p class="et-article__author-box-bio">{{ \Illuminate\Support\Str::limit(strip_tags($bio), 160) }}</p>
        <div class="et-article__author-box-meta">
            @if($publishedLabel)
                <span>{{ $publishedLabel }}</span>
            @endif
            @if($profileUrl)
                <a class="et-article__author-box-link" href="{{ $profileUrl }}">View profile</a>
            @endif
        </div>
    </div>
</section>
