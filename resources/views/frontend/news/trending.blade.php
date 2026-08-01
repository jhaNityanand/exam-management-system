@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Trending news',
        'description' => 'Trending education news and exam updates.',
        'image_type' => 'news',
    ];
    $activeFilterCount = collect([
        request('search'),
        request('sort') && request('sort') !== 'latest' ? request('sort') : null,
    ])->filter(fn ($v) => filled($v))->count();
@endphp

@section('content')
    @include('frontend.partials.listing-page', [
        'listingEndpoint' => route('frontend.news.trending'),
        'listingLoadMoreEndpoint' => route('frontend.news.trending', request()->query()),
        'listingModalId' => 'et-news-trending-filter-modal',
        'listingTitle' => 'Filter trending news',
        'listingHeading' => 'Trending news',
        'listingLead' => 'Stories gaining traction with aspirants right now.',
        'listingBreadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'News', 'url' => route('frontend.news.index')],
            ['label' => 'Trending'],
        ],
        'listingSearchLabel' => 'Search news',
        'listingSearchPlaceholder' => 'Search by title or topic…',
        'listingItems' => $news,
        'listingCard' => 'frontend.components.news-card',
        'listingCardKey' => 'news',
        'listingEmptyTitle' => 'No trending stories',
        'listingResetUrl' => route('frontend.news.trending'),
        'activeFilterCount' => $activeFilterCount,
    ])
@endsection
