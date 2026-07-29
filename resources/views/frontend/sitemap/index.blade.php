@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Sitemap',
        'description' => 'Browse all key pages on '.($siteBrand['name'] ?? config('app.name')).'.',
        'breadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Sitemap'],
        ],
    ];
@endphp

@section('content')
<section class="et-page-hero">
    <div class="et-container et-page-hero__inner">
        @include('frontend.partials.breadcrumbs', ['breadcrumbs' => $seo['breadcrumbs']])
        <p class="et-eyebrow">Explore</p>
        <h1>Sitemap</h1>
        <p class="et-page-hero__lead">Jump to any major section of the site.</p>
    </div>
</section>

<section class="et-section">
    <div class="et-container">
        <ul class="et-sitemap-grid">
            @foreach($links as $link)
                <li>
                    <a href="{{ $link['url'] }}" class="et-sitemap-card">
                        <span>{{ $link['label'] }}</span>
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 5.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/></svg>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endsection
