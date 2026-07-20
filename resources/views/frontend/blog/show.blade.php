@extends('frontend.layouts.app')

@php
    $words = str_word_count(strip_tags((string) ($blog->content ?? '')));
    $readingMins = max(1, (int) ceil($words / 200));
    $banner = method_exists($blog, 'bannerUrl') ? $blog->bannerUrl() : ($blog->bannerImage->file_url ?? null);
    $author = $blog->author_name ?: ($blog->author->name ?? 'Examtube Editorial');
    $seo = [
        'title' => $blog->seo_title ?: $blog->title,
        'description' => $blog->seo_description ?: ($blog->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $blog->content), 160)),
        'keywords' => $blog->seo_keywords,
        'canonical' => $blog->canonical_url ?: url()->current(),
        'og_title' => $blog->og_title,
        'og_description' => $blog->og_description,
        'image' => $blog->ogImage->file_url ?? $banner,
        'type' => 'article',
    ];
    $shareUrl = urlencode(url()->current());
    $shareText = urlencode($blog->title);
@endphp

@section('content')
    <article class="et-article">
        <div class="et-page-hero">
            <div class="et-container et-article__wrap">
                @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                    ['label' => 'Home', 'url' => route('home')],
                    ['label' => 'Blogs', 'url' => route('frontend.blogs.index')],
                    ['label' => $blog->title],
                ]])
                <div class="et-card__meta" style="margin-bottom:.5rem">
                    @if($blog->category)
                        <a class="et-badge" href="{{ route('frontend.blogs.category', $blog->category->slug) }}">{{ $blog->category->name }}</a>
                    @endif
                    <span>{{ $readingMins }} min read</span>
                    @if($blog->published_at)
                        <span>{{ $blog->published_at->format('d M Y') }}</span>
                    @endif
                </div>
                <h1>{{ $blog->title }}</h1>
                <p>By {{ $author }}</p>
            </div>
        </div>

        <div class="et-container et-article__wrap et-section">
            @if($banner)
                <div class="et-article-banner">
                    <img src="{{ $banner }}" alt="{{ $blog->title }}" loading="lazy" width="820" height="420">
                </div>
            @endif

            @if($blog->excerpt)
                <p class="et-article__lead">{{ $blog->excerpt }}</p>
            @endif

            @include('frontend.partials.article-toc', ['content' => $blog->content])

            <div class="et-prose">
                {!! $processedContent ?? $blog->content !!}
            </div>

            <div class="et-share">
                <span>Share</span>
                <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareText }}" target="_blank" rel="noopener">X</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener">Facebook</a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener">LinkedIn</a>
                <a href="https://wa.me/?text={{ $shareText }}%20{{ $shareUrl }}" target="_blank" rel="noopener">WhatsApp</a>
            </div>

            @if(($blog->tags ?? collect())->isNotEmpty())
                <div class="et-tag-cloud">
                    @foreach($blog->tags as $tag)
                        <a href="{{ route('frontend.blogs.tag', $tag->slug) }}">#{{ $tag->name }}</a>
                    @endforeach
                </div>
            @endif

            @php $relatedItems = $relatedBlogs ?? $related ?? collect(); @endphp
            @if($relatedItems->isNotEmpty())
                <aside class="et-related-rail">
                    @include('frontend.components.section-heading', [
                        'title' => 'Related posts',
                        'subtitle' => '',
                    ])
                    <div class="et-grid et-grid--2">
                        @foreach($relatedItems as $rel)
                            @include('frontend.components.blog-card', ['blog' => $rel])
                        @endforeach
                    </div>
                </aside>
            @endif
        </div>
    </article>
@endsection
