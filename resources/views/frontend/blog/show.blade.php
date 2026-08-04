@extends('frontend.layouts.app')

@php
    $words = str_word_count(strip_tags((string) ($blog->content ?? '')));
    $readingMins = max(1, (int) ceil($words / 200));
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
@endphp

@section('content')
<x-ad-layout page="blog_detail">
    <article class="et-article et-article--detail{{ $hasBanner ? ' et-article--has-banner' : ' et-article--no-banner' }}">
        <header class="et-article__top">
            <div class="et-container et-article__shell">
                @include('frontend.partials.breadcrumbs', ['breadcrumbs' => $crumbs])

                <div class="et-article__badges">
                    @if($blog->category)
                        <a class="et-badge et-article__badge" href="{{ route('frontend.blogs.category', $blog->category->slug) }}">{{ $blog->category->name }}</a>
                    @endif
                    <span class="et-badge et-badge--soft">{{ $readingMins }} min read</span>
                    @if($blog->published_at)
                        <span class="et-badge et-badge--soft">
                            <time datetime="{{ $blog->published_at->toIso8601String() }}">{{ $blog->published_at->format('d M Y') }}</time>
                        </span>
                    @endif
                </div>

                <x-ad-slot page="blog_detail" position="above_title" />

                <h1 class="et-article__title">{{ $blog->title }}</h1>

                <x-ad-slot page="blog_detail" position="below_title" />
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
                    @endif

                    <x-ad-slot page="blog_detail" position="before_content" />

                    @if($blog->excerpt)
                        <p class="et-article__lead">{{ $blog->excerpt }}</p>
                    @endif

                    @php
                        $tocData = build_article_toc($processedContent ?? $blog->content);
                        $processedContent = $tocData['html'];
                        $tocItems = $tocData['items'];
                    @endphp

                    @include('frontend.partials.article-toc', ['tocItems' => $tocItems])

                    <div class="et-prose et-article__prose">
                        {!! $processedContent !!}
                    </div>

                    <x-ad-slot page="blog_detail" position="between_sections" />

                    <div class="et-article__footer-panel">
                        @include('frontend.partials.article-share', [
                            'shareUrl' => $shareUrl,
                            'shareText' => $shareText,
                            'shareRawUrl' => $shareRawUrl,
                            'shareLabel' => 'Share this article',
                        ])
                    </div>

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
                    @endif

                    <x-ad-slot page="blog_detail" position="after_related" />
                    <x-ad-slot page="blog_detail" position="after_newsletter" />
                    <x-ad-slot page="blog_detail" position="after_cta" />
                </div>

                @include('frontend.partials.article-aside')
            </div>
        </div>
    </article>
</x-ad-layout>
@endsection
