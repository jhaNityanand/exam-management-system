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
@endphp

@section('content')
    <article>
        <div class="et-page-hero">
            <div class="et-container" style="max-width:820px;margin-inline:auto">
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

        <div class="et-container" style="max-width:820px;margin-inline:auto;padding-bottom:3rem">
            @if($banner)
                <div class="et-article-banner">
                    <img src="{{ $banner }}" alt="">
                </div>
            @endif

            @if($blog->excerpt)
                <p style="font-size:1.15rem;color:var(--et-text-muted);margin:0 0 1.25rem">{{ $blog->excerpt }}</p>
            @endif

            <div class="et-prose">
                {!! $blog->content !!}
            </div>

            @if(($blog->tags ?? collect())->isNotEmpty())
                <div class="et-tag-cloud" style="margin-top:1.75rem">
                    @foreach($blog->tags as $tag)
                        <a href="{{ route('frontend.blogs.tag', $tag->slug) }}">#{{ $tag->name }}</a>
                    @endforeach
                </div>
            @endif

            @if(($related ?? collect())->isNotEmpty())
                <section style="margin-top:2.5rem">
                    @include('frontend.components.section-heading', [
                        'title' => 'Related posts',
                        'subtitle' => '',
                    ])
                    <div class="et-grid et-grid--2">
                        @foreach($related as $rel)
                            @include('frontend.components.blog-card', ['blog' => $rel])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </article>
@endsection
