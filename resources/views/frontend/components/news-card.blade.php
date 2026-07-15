@php
    $newsUrl = Route::has('frontend.news.show')
        ? route('frontend.news.show', $news->slug)
        : '#';
    $image = $news->featuredImage->file_url
        ?? $news->bannerImage->file_url
        ?? (method_exists($news, 'bannerUrl') ? $news->bannerUrl() : null);
@endphp
<article class="et-card et-news-card">
    <a href="{{ $newsUrl }}" class="et-card__media" tabindex="-1" aria-hidden="true">
        @if($image)
            <img src="{{ $image }}" alt="" loading="lazy">
        @endif
    </a>
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
