@php
    $newsUrl = Route::has('frontend.news.show')
        ? route('frontend.news.show', $news->slug)
        : '#';
    $images = method_exists($news, 'cardImageUrls')
        ? $news->cardImageUrls()
        : array_values(array_filter([
            $news->featuredImage->file_url ?? null,
            $news->bannerImage->file_url ?? null,
            method_exists($news, 'bannerUrl') ? $news->bannerUrl() : null,
        ]));
@endphp
<article class="et-card et-news-card">
    @include('frontend.partials.card-media', [
        'images' => $images,
        'href' => $newsUrl,
        'alt' => $news->title,
    ])
    <div class="et-card__body">
        <div class="et-card__meta">
            @if($news->is_breaking)
                <span class="et-badge et-badge--danger">Breaking</span>
            @endif
            @if($news->is_trending)
                <span class="et-badge et-badge--warn">Trending</span>
            @endif
            @if($news->category)
                <span class="et-badge">{{ $news->category->name }}</span>
            @endif
            @if($news->published_at)
                <span>{{ $news->published_at->diffForHumans() }}</span>
            @endif
        </div>
        <h3 class="et-card__title"><a href="{{ $newsUrl }}">{{ $news->title }}</a></h3>
        @php $summary = $news->short_description ?? $news->excerpt ?? null; @endphp
        @if($summary)
            <p class="et-card__excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($summary), 130) }}</p>
        @endif
        <div class="et-card__footer">
            <span class="et-card__meta">{{ $news->author_name ?: ($news->author->name ?? 'News Desk') }}</span>
            <a href="{{ $newsUrl }}" class="et-btn et-btn--soft et-btn--sm">Open</a>
        </div>
    </div>
</article>
