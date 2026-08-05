@extends('frontend.layouts.app')

@php
    $article = $news ?? $article ?? null;
    $bannerImages = method_exists($article, 'cardImageUrls')
        ? $article->cardImageUrls()
        : array_values(array_filter(array_unique(array_merge(
            [$article->featuredImageUrl()],
            $article->bannerUrls()
        ))));
    $hasBanner = count($bannerImages) > 0;
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
        'breadcrumbs' => $crumbs,
        'published_time' => optional($article->published_at)?->toAtomString(),
        'author' => $article->author?->name,
    ];
    $shareUrl = urlencode(url()->current());
    $shareText = urlencode($article->title);
    $shareRawUrl = url()->current();
    $categoryTrail = collect();
    $categoryCursor = $article->category;
    $categoryGuard = 0;
    while ($categoryCursor && $categoryGuard < 12) {
        $categoryTrail->prepend($categoryCursor);
        $categoryCursor = $categoryCursor->parent;
        $categoryGuard++;
    }
    $publishedLabel = $article->published_at
        ? $article->published_at->format('d M Y, H:i')
        : null;
    $articleHeaderMeta = collect([
        $article->is_breaking ? ['label' => 'Breaking', 'tone' => 'danger'] : null,
        $article->is_trending ? ['label' => 'Trending', 'tone' => 'warn'] : null,
    ])->filter()->values();
@endphp

@section('content')
<article class="et-article et-article--detail et-article--news{{ $hasBanner ? ' et-article--has-banner' : ' et-article--no-banner' }}">
        <header class="et-article__top">
            <div class="et-container et-article__shell">
                @include('frontend.partials.breadcrumbs', ['breadcrumbs' => $crumbs])

                <h1 class="et-article__title">{{ $article->title }}</h1>
                @include('frontend.partials.detail-header-meta', [
                    'categoryTrail' => $categoryTrail,
                    'categoryUrlFn' => fn ($category) => route('frontend.news.category', $category->slug),
                    'publishedLabel' => $publishedLabel ? 'Published '.$publishedLabel : null,
                    'publishedDatetime' => optional($article->published_at)?->toIso8601String(),
                ])
            </div>
        </header>

        <div class="et-container et-article__shell et-article__body">
            <div class="et-article__layout">
                <div class="et-article__primary">
                    @if($hasBanner)
                        @include('frontend.partials.article-banner', [
                            'images' => $bannerImages,
                            'alt' => $article->title,
                        ])
                    @endif

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

<div class="et-article__footer-panel">
                        @include('frontend.partials.article-share', [
                            'shareUrl' => $shareUrl,
                            'shareText' => $shareText,
                            'shareRawUrl' => $shareRawUrl,
                            'shareLabel' => 'Share this story',
                        ])
                    </div>

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
                </div>

                @include('frontend.partials.article-aside')
            </div>
        </div>
    </article>
@endsection
