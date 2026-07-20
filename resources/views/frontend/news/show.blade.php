@extends('frontend.layouts.app')

@php
    $article = $news ?? $article ?? null;
    $banner = $article->featuredImageUrl()
        ?? (method_exists($article, 'bannerUrl') ? $article->bannerUrl() : null)
        ?? ($article->bannerImage->file_url ?? null);
    $seo = [
        'title' => $article->seo_title ?: $article->title,
        'description' => $article->seo_description ?: ($article->short_description ?: $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $article->content), 160)),
        'keywords' => $article->seo_keywords,
        'canonical' => $article->canonical_url ?: url()->current(),
        'og_title' => $article->og_title,
        'og_description' => $article->og_description,
        'image' => $article->ogImage->file_url ?? $banner,
        'type' => 'article',
    ];
    $shareUrl = urlencode(url()->current());
    $shareText = urlencode($article->title);
@endphp

@section('content')
    <article class="et-article">
        <div class="et-page-hero">
            <div class="et-container et-article__wrap">
                @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                    ['label' => 'Home', 'url' => route('home')],
                    ['label' => 'News', 'url' => route('frontend.news.index')],
                    ['label' => $article->title],
                ]])
                <div class="et-card__meta" style="margin-bottom:.5rem">
                    @if($article->is_breaking)
                        <span class="et-badge et-badge--danger">Breaking</span>
                    @endif
                    @if($article->is_trending)
                        <span class="et-badge et-badge--warn">Trending</span>
                    @endif
                    @if($article->category)
                        <a class="et-badge" href="{{ route('frontend.news.category', $article->category->slug) }}">{{ $article->category->name }}</a>
                    @endif
                    @if($article->published_at)
                        <span>{{ $article->published_at->format('d M Y, H:i') }}</span>
                    @endif
                </div>
                <h1>{{ $article->title }}</h1>
                <p>{{ $article->author_name ?: ($article->author->name ?? 'News Desk') }}</p>
            </div>
        </div>

        <div class="et-container et-article__wrap et-section">
            @if($banner)
                <div class="et-article-banner">
                    <img src="{{ $banner }}" alt="{{ $article->title }}" loading="lazy" width="820" height="420">
                </div>
            @endif

            @php $summary = $article->short_description ?? $article->excerpt; @endphp
            @if($summary)
                <p class="et-article__lead">{{ $summary }}</p>
            @endif

            @include('frontend.partials.article-toc', ['content' => $article->content])

            <div class="et-prose">
                {!! $processedContent ?? $article->content !!}
            </div>

            <div class="et-share">
                <span>Share</span>
                <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareText }}" target="_blank" rel="noopener">X</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener">Facebook</a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener">LinkedIn</a>
                <a href="https://wa.me/?text={{ $shareText }}%20{{ $shareUrl }}" target="_blank" rel="noopener">WhatsApp</a>
            </div>

            @if(($article->tags ?? collect())->isNotEmpty())
                <div class="et-tag-cloud">
                    @foreach($article->tags as $tag)
                        <a href="{{ route('frontend.news.tag', $tag->slug) }}">#{{ $tag->name }}</a>
                    @endforeach
                </div>
            @endif

            @php $relatedItems = $relatedNews ?? $related ?? collect(); @endphp
            @if($relatedItems->isNotEmpty())
                <aside class="et-related-rail">
                    @include('frontend.components.section-heading', ['title' => 'More news', 'subtitle' => ''])
                    <div class="et-grid et-grid--2">
                        @foreach($relatedItems as $item)
                            @include('frontend.components.news-card', ['news' => $item])
                        @endforeach
                    </div>
                </aside>
            @endif
        </div>
    </article>
@endsection
