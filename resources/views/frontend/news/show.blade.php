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
        'image' => $article->ogImage->file_url ?? $banner,
        'type' => 'article',
    ];
@endphp

@section('content')
    <article>
        <div class="et-page-hero">
            <div class="et-container" style="max-width:820px;margin-inline:auto">
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

        <div class="et-container" style="max-width:820px;margin-inline:auto;padding-bottom:3rem">
            @if($banner)
                <div class="et-article-banner">
                    <img src="{{ $banner }}" alt="">
                </div>
            @endif

            @php $summary = $article->short_description ?? $article->excerpt; @endphp
            @if($summary)
                <p style="font-size:1.15rem;color:var(--et-text-muted);margin:0 0 1.25rem">{{ $summary }}</p>
            @endif

            <div class="et-prose">
                {!! $article->content !!}
            </div>

            @if(($related ?? collect())->isNotEmpty())
                <section style="margin-top:2.5rem">
                    @include('frontend.components.section-heading', ['title' => 'More news', 'subtitle' => ''])
                    <div class="et-grid et-grid--2">
                        @foreach($related as $item)
                            @include('frontend.components.news-card', ['news' => $item])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </article>
@endsection
