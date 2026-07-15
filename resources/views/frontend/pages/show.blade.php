@extends('frontend.layouts.app')

@php
    $isContact = ($page->template ?? 'default') === 'contact';
    $seo = [
        'title' => $page->seo_title ?: $page->title.' | '.($siteBrand['name'] ?? 'Examtube.in'),
        'description' => $page->seo_description ?: ($page->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $page->content), 160)),
        'keywords' => $page->seo_keywords,
    ];
    $banner = $page->bannerImage->file_url ?? null;
@endphp

@section('content')
    <section class="et-page-hero">
        <div class="et-container et-page-hero__inner">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => $page->title],
            ]])
            <p class="et-eyebrow">Examtube.in</p>
            <h1>{{ $page->title }}</h1>
            @if ($page->excerpt)
                <p class="et-page-hero__lead">{{ $page->excerpt }}</p>
            @endif
        </div>
    </section>

    <div class="et-container et-page-body">
        @if ($banner)
            <figure class="et-article-banner">
                <img src="{{ $banner }}" alt="{{ $page->title }}">
            </figure>
        @endif

        @if ($isContact)
            @include('frontend.pages.contact', ['page' => $page])
        @else
            <article class="et-panel et-panel--article">
                <div class="et-prose">
                    {!! $page->content !!}
                </div>
            </article>
        @endif
    </div>
@endsection
