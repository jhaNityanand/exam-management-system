@extends('frontend.layouts.app')

@php
    $bannerImages = $blog->bannerUrls();
    $hasBanner = count($bannerImages) > 0;
    $crumbs = [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Blogs', 'url' => route('frontend.blogs.index')],
    ];
    if ($blog->category) {
        $crumbs[] = [
            'label' => $blog->category->name,
            'url' => route('frontend.blogs.category', $blog->category->slug),
        ];
    }
    $crumbs[] = ['label' => 'Article'];
    $seo = [
        'title' => $blog->seo_title ?: $blog->title,
        'description' => $blog->seo_description ?: ($blog->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $blog->content), 160)),
        'keywords' => $blog->seo_keywords,
        'canonical' => $blog->canonical_url ?: url()->current(),
        'og_title' => $blog->og_title,
        'og_description' => $blog->og_description,
        'image' => $blog->seoImageUrl(),
        'image_type' => 'blog',
        'type' => 'article',
        'breadcrumbs' => $crumbs,
    ];
    $shareUrl = urlencode(url()->current());
    $shareText = urlencode($blog->title);
    $shareRawUrl = url()->current();
    $categoryTrail = collect();
    $categoryCursor = $blog->category;
    $categoryGuard = 0;
    while ($categoryCursor && $categoryGuard < 12) {
        $categoryTrail->prepend($categoryCursor);
        $categoryCursor = $categoryCursor->parent;
        $categoryGuard++;
    }
    $publishedLabel = $blog->published_at ? $blog->published_at->format('d M Y') : null;
@endphp

@section('content')
<article class="et-article et-article--detail{{ $hasBanner ? ' et-article--has-banner' : ' et-article--no-banner' }}">
        <header class="et-article__top">
            <div class="et-container et-article__shell">
                @include('frontend.partials.breadcrumbs', ['breadcrumbs' => $crumbs])

                <h1 class="et-article__title">{{ $blog->title }}</h1>
                @include('frontend.partials.detail-header-meta', [
                    'categoryTrail' => $categoryTrail,
                    'categoryUrlFn' => fn ($category) => route('frontend.blogs.category', $category->slug),
                    'publishedLabel' => $publishedLabel ? 'Published '.$publishedLabel : null,
                    'publishedDatetime' => optional($blog->published_at)?->toIso8601String(),
                ])
                @include('frontend.partials.ad-placement', ['page' => 'blog_detail', 'position' => 'below_title'])
            </div>
        </header>

        <div class="et-container et-article__shell et-article__body">
            <div class="et-article__layout">
                <div class="et-article__primary">
                    @if($hasBanner)
                        @include('frontend.partials.article-banner', [
                            'images' => $bannerImages,
                            'alt' => $blog->title,
                        ])
                        @include('frontend.partials.ad-placement', ['page' => 'blog_detail', 'position' => 'before_content'])
                    @endif

@if($blog->excerpt)
                        <p class="et-article__lead">{{ $blog->excerpt }}</p>
                    @endif

                    @php
                        $tocData = build_article_toc($processedContent ?? $blog->content);
                        $processedContent = $tocData['html'];
                        $tocItems = $tocData['items'];
                        $processedContent = app(\App\Services\Advertisement\AdvertisementService::class)
                            ->injectIntoContent($processedContent, 'blog_detail');
                    @endphp

                    @include('frontend.partials.article-toc', ['tocItems' => $tocItems])

                    <div class="et-prose et-article__prose">
                        {!! $processedContent !!}
                    </div>
                    @include('frontend.partials.ad-placement', ['page' => 'blog_detail', 'position' => 'between_sections'])

<div class="et-article__footer-panel">
                        @include('frontend.partials.article-share', [
                            'shareUrl' => $shareUrl,
                            'shareText' => $shareText,
                            'shareRawUrl' => $shareRawUrl,
                            'shareLabel' => 'Share this article',
                        ])
                    </div>
                    @include('frontend.partials.ad-placement', ['page' => 'blog_detail', 'position' => 'after_share'])

                    @php $relatedItems = $relatedBlogs ?? $related ?? collect(); @endphp
                    @if($relatedItems->isNotEmpty())
                        <section class="et-article__related">
                            @include('frontend.components.section-heading', [
                                'title' => 'Related posts',
                                'subtitle' => 'More guides connected to this topic',
                            ])
                            <div class="et-grid et-grid--3 et-article__related-grid">
                                @foreach($relatedItems->take(3) as $rel)
                                    @include('frontend.components.blog-card', ['blog' => $rel])
                                @endforeach
                            </div>
                        </section>
                        @include('frontend.partials.ad-placement', ['page' => 'blog_detail', 'position' => 'after_related'])
                    @endif

                </div>

                @include('frontend.partials.article-aside')
            </div>
        </div>
    </article>
@endsection
