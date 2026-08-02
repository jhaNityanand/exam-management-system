@extends('frontend.layouts.app')

@php
    $slug = $page->slug ?? '';
    $template = $page->template ?? 'default';

    $viewKey = match (true) {
        $template === 'contact' || $slug === 'contact-us' => 'contact',
        $template === 'about' || $slug === 'about-us' => 'about',
        $template === 'privacy' || $slug === 'privacy-policy' => 'privacy',
        $template === 'terms' || $slug === 'terms-and-conditions' => 'terms',
        default => 'default',
    };

    $eyebrow = match ($viewKey) {
        'about' => 'About Examtube',
        'contact' => 'Support',
        'privacy', 'terms' => 'Legal',
        default => 'Examtube.in',
    };

    $seo = [
        'title' => $page->seo_title ?: $page->title.' | '.($siteBrand['name'] ?? 'Examtube.in'),
        'description' => $page->seo_description ?: ($page->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $page->content), 160)),
        'keywords' => $page->seo_keywords,
        'image' => $page->seoImageUrl(),
        'image_type' => \App\Support\SeoImage::typeForCmsPage($page->template, $page->slug),
    ];
    $banner = $page->bannerImage->file_url ?? null;
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/frontend/static-pages.css') }}">
@endpush

@section('content')
<x-ad-layout page="cms_page">
    @if ($viewKey === 'about')
        @include('frontend.pages.about', ['page' => $page, 'eyebrow' => $eyebrow])
    @elseif ($viewKey === 'contact')
        @include('frontend.pages.contact', ['page' => $page, 'eyebrow' => $eyebrow, 'banner' => $banner])
    @elseif ($viewKey === 'privacy')
        @include('frontend.pages.privacy', ['page' => $page, 'eyebrow' => $eyebrow])
    @elseif ($viewKey === 'terms')
        @include('frontend.pages.terms', ['page' => $page, 'eyebrow' => $eyebrow])
    @else
        <section class="et-sp-hero">
            <div class="et-container">
                @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                    ['label' => 'Home', 'url' => route('home')],
                    ['label' => $page->title],
                ]])
                <p class="et-eyebrow">{{ $eyebrow }}</p>
                <h1>{{ $page->title }}</h1>
                @if ($page->excerpt)
                    <p class="et-sp-hero__lead">{{ $page->excerpt }}</p>
                @endif
            </div>
        </section>
        <x-ad-slot page="cms_page" position="below_title" />
        <div class="et-container et-page-body">
            @if ($banner)
                <figure class="et-article-banner">
                    <img src="{{ $banner }}" alt="{{ $page->title }}">
                </figure>
            @endif
            <article class="et-panel et-panel--article et-cms-article">
                <div class="et-prose">{!! $page->content !!}</div>
            </article>
            <x-ad-slot page="cms_page" position="after_content" />
        </div>
    @endif
    @if (in_array($viewKey, ['about', 'contact', 'privacy', 'terms'], true))
        <x-ad-slot page="cms_page" position="after_content" />
    @endif
</x-ad-layout>
@endsection
