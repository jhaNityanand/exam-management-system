@extends('frontend.layouts.app')

@php
    $article = $news ?? $article ?? null;
    $words = str_word_count(strip_tags((string) ($article->content ?? '')));
    $readingMins = max(1, (int) ceil($words / 200));
    $seo = [
        'title' => $article->seo_title ?: $article->title,
        'description' => $article->seo_description ?: ($article->short_description ?: $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $article->content), 160)),
        'keywords' => $article->seo_keywords,
        'canonical' => $article->canonical_url ?: url()->current(),
        'og_title' => $article->og_title,
        'og_description' => $article->og_description,
        'image' => $article->seoImageUrl(),
        'image_type' => 'news',
        'type' => 'article',
    ];
    $shareUrl = urlencode(url()->current());
    $shareText = urlencode($article->title);
    $shareRawUrl = url()->current();
    $summary = $article->short_description ?? $article->excerpt;
    $crumbs = [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'News', 'url' => route('frontend.news.index')],
    ];
    if ($article->category) {
        $crumbs[] = [
            'label' => $article->category->name,
            'url' => route('frontend.news.category', $article->category->slug),
        ];
    }
    $crumbs[] = ['label' => 'Article'];
@endphp

@section('content')
    <article class="et-article et-article--news">
        <header class="et-article__top">
            <div class="et-container et-article__wrap">
                @include('frontend.partials.breadcrumbs', ['breadcrumbs' => $crumbs])

                <div class="et-article__badges">
                    @if($article->is_breaking)
                        <span class="et-badge et-badge--danger">Breaking</span>
                    @endif
                    @if($article->is_trending)
                        <span class="et-badge et-badge--warn">Trending</span>
                    @endif
                    @if($article->category)
                        <a class="et-badge et-article__badge" href="{{ route('frontend.news.category', $article->category->slug) }}">{{ $article->category->name }}</a>
                    @endif
                    <span class="et-badge et-badge--soft">{{ $readingMins }} min read</span>
                    @if($article->published_at)
                        <span class="et-badge et-badge--soft">
                            <time datetime="{{ $article->published_at->toIso8601String() }}">{{ $article->published_at->format('d M Y, H:i') }}</time>
                        </span>
                    @endif
                </div>

                {!! ad_slot('news_detail_above_h1') !!}

                <h1 class="et-article__title">{{ $article->title }}</h1>
            </div>
        </header>

        <div class="et-container et-article__wrap et-article__main">
            @if($banner)
                <figure class="et-article-banner">
                    <img src="{{ $banner }}" alt="{{ $article->title }}" loading="eager" width="960" height="480">
                </figure>
            @endif

            {!! ad_slot('news_detail_sidebar_top') !!}

            @if($summary)
                <p class="et-article__lead">{{ $summary }}</p>
            @endif

            @php
                $tocData = build_article_toc($processedContent ?? $article->content);
                $processedContent = $tocData['html'];
                $tocItems = $tocData['items'];
            @endphp

            @include('frontend.partials.article-toc', ['tocItems' => $tocItems])

            <div class="et-prose et-article__prose">
                {!! $processedContent !!}
            </div>

            @include('frontend.partials.article-author', [
                'authorUser' => $article->author,
                'authorName' => $article->author_name ?: ($article->author->name ?? null),
                'fallbackName' => 'News Desk',
                'defaultBio' => 'Covering exam alerts, campus updates, and aspirant-focused news on Examtube.',
                'publishedLabel' => $article->published_at
                    ? 'Published '.$article->published_at->format('d M Y, H:i')
                    : null,
            ])

            {!! ad_slot('news_detail_sidebar_middle') !!}

            <div class="et-article__footer-panel">
                @if(($article->tags ?? collect())->isNotEmpty())
                    <div class="et-article__tags">
                        <span class="et-article__share-label">Tags</span>
                        <div class="et-article__tags-list">
                            @foreach($article->tags as $newsTag)
                                <a class="et-article__tag" href="{{ route('frontend.news.tag', $newsTag->slug) }}">#{{ $newsTag->name }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @include('frontend.partials.article-share', [
                    'shareUrl' => $shareUrl,
                    'shareText' => $shareText,
                    'shareRawUrl' => $shareRawUrl,
                    'shareLabel' => 'Share this story',
                ])
            </div>

            {!! ad_slot('news_detail_before_comments') !!}
            {!! ad_slot('news_detail_sidebar_bottom') !!}

            @php $relatedItems = $relatedNews ?? $related ?? collect(); @endphp
            @if($relatedItems->isNotEmpty())
                <section class="et-article__related">
                    @include('frontend.components.section-heading', [
                        'title' => 'More news',
                        'subtitle' => 'Stories connected to this update',
                    ])
                    <div class="et-grid et-grid--3 et-article__related-grid">
                        @foreach($relatedItems->take(3) as $item)
                            @include('frontend.components.news-card', ['news' => $item])
                        @endforeach
                    </div>
                </section>
            @endif

            @php $moreItems = $trendingNews ?? collect(); @endphp
            @if($moreItems->isNotEmpty())
                <section class="et-article__more">
                    @include('frontend.components.section-heading', [
                        'title' => 'Trending now',
                        'subtitle' => 'What aspirants are reading today',
                    ])
                    <div class="et-article__more-list">
                        @foreach($moreItems as $item)
                            @php
                                $itemUrl = route('frontend.news.show', $item->slug);
                                $itemBanner = $item->featuredImageUrl()
                                    ?? (method_exists($item, 'bannerUrl') ? $item->bannerUrl() : null)
                                    ?? ($item->bannerImage->file_url ?? null);
                            @endphp
                            <a class="et-article__more-item" href="{{ $itemUrl }}">
                                <span class="et-article__more-thumb">
                                    @if($itemBanner)
                                        <img src="{{ $itemBanner }}" alt="" loading="lazy">
                                    @endif
                                </span>
                                <span class="et-article__more-copy">
                                    <span class="et-article__more-kicker">
                                        @if($item->is_breaking) Breaking · @endif
                                        @if($item->category){{ $item->category->name }}@else News @endif
                                    </span>
                                    <span class="et-article__more-title">{{ $item->title }}</span>
                                    @if($item->published_at)
                                        <span class="et-article__more-meta">{{ $item->published_at->diffForHumans() }}</span>
                                    @endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="et-article__newsletter">
                <div class="et-newsletter-band et-newsletter-band--compact">
                    <div class="et-newsletter-band__copy">
                        <p class="et-eyebrow">News alerts</p>
                        <h2>Don’t miss exam updates</h2>
                        <p>Breaking alerts, trending stories, and weekly digests — straight to your inbox.</p>
                        @include('frontend.partials.newsletter-form', [
                            'cta' => 'Subscribe',
                            'source' => 'news_detail',
                        ])
                    </div>
                </div>
            </section>

            <section class="et-article__cta">
                @include('frontend.components.cta-band', [
                    'title' => 'Stay ahead of the cycle',
                    'subtitle' => 'Browse more news or catch up on trending updates.',
                    'primaryLabel' => 'All news',
                    'primaryUrl' => route('frontend.news.index'),
                    'secondaryLabel' => Route::has('frontend.news.trending') ? 'Trending news' : ($article->category ? 'More in '.$article->category->name : 'Browse blogs'),
                    'secondaryUrl' => Route::has('frontend.news.trending')
                        ? route('frontend.news.trending')
                        : ($article->category
                            ? route('frontend.news.category', $article->category->slug)
                            : route('frontend.blogs.index')),
                ])
            </section>
        </div>
    </article>
@endsection
