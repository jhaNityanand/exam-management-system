@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'News tagged '.$tag->name, 'description' => 'News tagged with '.$tag->name];
    $activeFilterCount = collect([
        request('search'),
        request('sort') && request('sort') !== 'latest' ? request('sort') : null,
    ])->filter(fn ($v) => filled($v))->count();
@endphp

@section('content')
    @include('frontend.partials.listing-page', [
        'listingEndpoint' => route('frontend.news.tag', $tag->slug),
        'listingLoadMoreEndpoint' => route('frontend.news.tag', $tag->slug).(request()->getQueryString() ? '?'.request()->getQueryString() : ''),
        'listingModalId' => 'et-news-tag-filter-modal',
        'listingTitle' => 'Filter news',
        'listingHeading' => '#'.$tag->name,
        'listingLead' => 'News stories tagged with '.$tag->name.'.',
        'listingBreadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'News', 'url' => route('frontend.news.index')],
            ['label' => '#'.$tag->name],
        ],
        'listingSearchLabel' => 'Search news',
        'listingSearchPlaceholder' => 'Search by title or topic…',
        'listingItems' => $news,
        'listingCard' => 'frontend.components.news-card',
        'listingCardKey' => 'news',
        'listingEmptyTitle' => 'No news for this tag',
        'listingResetUrl' => route('frontend.news.tag', $tag->slug),
        'activeFilterCount' => $activeFilterCount,
    ])
@endsection
