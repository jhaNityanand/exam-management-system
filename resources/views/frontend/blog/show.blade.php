@extends('frontend.layouts.app')

@php
    $words = str_word_count(strip_tags((string) ($blog->content ?? '')));
    $readingMins = max(1, (int) ceil($words / 200));
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
    $banner = $blog->bannerUrl();
@endphp

@section('content')
    <article class="et-article">
        <header class="et-article__top">
            <div class="et-container et-article__wrap">
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

                {!! ad_slot('blog_detail_above_h1') !!}

                <h1 class="et-article__title">{{ $blog->title }}</h1>
            </div>
        </header>

        <div class="et-container et-article__wrap et-article__main">
            @if($banner)
                <figure class="et-article-banner">
                    <img src="{{ $banner }}" alt="{{ $blog->title }}" loading="eager" width="960" height="480">
                </figure>
            @endif

            {!! ad_slot('blog_detail_sidebar_top') !!}

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

            @include('frontend.partials.article-author', [
                'authorUser' => $blog->author,
                'authorName' => $blog->author_name ?: ($blog->author->name ?? null),
                'fallbackName' => 'Examtube Editorial',
                'defaultBio' => 'Sharing practice insights, guides, and learning updates on Examtube.',
                'publishedLabel' => $blog->published_at
                    ? 'Published '.$blog->published_at->format('d M Y')
                    : null,
            ])

            {!! ad_slot('blog_detail_sidebar_middle') !!}

            <div class="et-article__footer-panel">
                @if(($blog->tags ?? collect())->isNotEmpty())
                    <div class="et-article__tags">
                        <span class="et-article__share-label">Tags</span>
                        <div class="et-article__tags-list">
                            @foreach($blog->tags as $blogTag)
                                <a class="et-article__tag" href="{{ route('frontend.blogs.tag', $blogTag->slug) }}">#{{ $blogTag->name }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @include('frontend.partials.article-share', [
                    'shareUrl' => $shareUrl,
                    'shareText' => $shareText,
                    'shareRawUrl' => $shareRawUrl,
                    'shareLabel' => 'Share this article',
                ])
            </div>

            {!! ad_slot('blog_detail_before_comments') !!}
            {!! ad_slot('blog_detail_sidebar_bottom') !!}

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

            <section class="et-article__newsletter">
                <div class="et-newsletter-band et-newsletter-band--panel">
                    <div class="et-newsletter-band__copy">
                        <p class="et-eyebrow">Newsletter</p>
                        <h2>Get new blogs in your inbox</h2>
                        <p>Weekly prep tips, strategy notes, and updates — no spam.</p>
                        @include('frontend.partials.newsletter-form', [
                            'cta' => 'Subscribe',
                            'source' => 'blog_detail',
                        ])
                    </div>
                    <div class="et-newsletter-band__art" aria-hidden="true">
                        <img src="{{ asset('frontend/images/newsletter.svg') }}" alt="" loading="lazy" width="320" height="240">
                    </div>
                </div>
            </section>

            <section class="et-article__cta">
                @include('frontend.components.cta-band', [
                    'title' => 'Keep exploring',
                    'subtitle' => 'Browse more blogs or jump into practice exams.',
                    'primaryLabel' => 'All blogs',
                    'primaryUrl' => route('frontend.blogs.index'),
                    'secondaryLabel' => $blog->category ? 'More in '.$blog->category->name : 'Browse exams',
                    'secondaryUrl' => $blog->category
                        ? route('frontend.blogs.category', $blog->category->slug)
                        : (Route::has('frontend.exams.index') ? route('frontend.exams.index') : route('home')),
                ])
            </section>
        </div>
    </article>

    @include('frontend.partials.detail-sidebar')
@endsection
