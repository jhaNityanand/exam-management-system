@extends('frontend.layouts.app')

@php
    $isContact = ($page->template ?? 'default') === 'contact';
    $slug = $page->slug ?? '';
    $eyebrow = match ($slug) {
        'about-us' => 'Our story',
        'privacy-policy' => 'Legal',
        'terms-and-conditions' => 'Legal',
        'contact-us' => 'Support',
        default => 'Examtube.in',
    };
    $seo = [
        'title' => $page->seo_title ?: $page->title.' | '.($siteBrand['name'] ?? 'Examtube.in'),
        'description' => $page->seo_description ?: ($page->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $page->content), 160)),
        'keywords' => $page->seo_keywords,
    ];
    $banner = $page->bannerImage->file_url ?? null;
@endphp

@section('content')
    <section class="et-page-hero et-page-hero--cms @if($isContact) et-page-hero--contact @endif">
        <div class="et-container et-page-hero__inner">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => $page->title],
            ]])
            <p class="et-eyebrow">{{ $eyebrow }}</p>
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
            <div class="et-cms-layout">
                <article class="et-panel et-panel--article et-cms-article">
                    <div class="et-prose">
                        {!! $page->content !!}
                    </div>
                </article>

                <aside class="et-cms-aside">
                    <div class="et-panel et-cms-aside__card">
                        <h2 class="et-panel__title">Need help?</h2>
                        <p class="et-panel__subtitle">Browse FAQs or reach the support team directly.</p>
                        <div class="et-cms-aside__actions">
                            <a href="{{ route('frontend.faqs.index') }}" class="et-btn et-btn--soft et-btn--sm">View FAQs</a>
                            <a href="{{ url('/contact-us') }}" class="et-btn et-btn--primary et-btn--sm">Contact us</a>
                        </div>
                    </div>
                    @if(in_array($slug, ['privacy-policy', 'terms-and-conditions'], true))
                        <div class="et-panel et-cms-aside__card">
                            <h2 class="et-panel__title">Related</h2>
                            <ul class="et-cms-aside__links">
                                @if($slug !== 'privacy-policy')
                                    <li><a href="{{ url('/privacy-policy') }}">Privacy Policy</a></li>
                                @endif
                                @if($slug !== 'terms-and-conditions')
                                    <li><a href="{{ url('/terms-and-conditions') }}">Terms &amp; Conditions</a></li>
                                @endif
                                <li><a href="{{ url('/about-us') }}">About Us</a></li>
                            </ul>
                        </div>
                    @endif
                </aside>
            </div>
        @endif
    </div>
@endsection
