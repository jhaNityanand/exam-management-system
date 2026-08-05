@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => ($category->name ?? 'Category').' news',
        'description' => $category->description ?? 'News in this category.',
        'image' => $category->seoImageUrl(),
        'image_type' => 'category',
    ];
    $activeFilterCount = collect([
        request('search'),
        request('sort') && request('sort') !== 'latest' ? request('sort') : null,
    ])->filter(fn ($v) => filled($v))->count();
@endphp

@section('content')
    @include('frontend.partials.listing-page', [
        'listingEndpoint' => route('frontend.news.category', $category->slug),
        'listingLoadMoreEndpoint' => route('frontend.news.category', $category->slug).(request()->getQueryString() ? '?'.request()->getQueryString() : ''),
        'listingModalId' => 'et-news-category-filter-modal',
        'listingTitle' => 'Filter news',
        'listingHeading' => $category->name ?? 'Category',
        'listingLead' => $category->description ?? null,
        'listingBreadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'News', 'url' => route('frontend.news.index')],
            ['label' => $category->name ?? 'Category'],
        ],
        'listingSearchLabel' => 'Search news',
        'listingSearchPlaceholder' => 'Search by title or topic…',
        'listingItems' => $news,
        'listingCard' => 'frontend.components.news-card',
        'listingCardKey' => 'news',
        'listingEmptyTitle' => 'No news in this category',
        'listingResetUrl' => route('frontend.news.category', $category->slug),
        'activeFilterCount' => $activeFilterCount,
        'listingGridClass' => 'et-grid et-grid--3',
        'listingSkeletonCount' => 6,
        'categoryNav' => $categoryNav ?? null,
        'categoryNavTitle' => 'Subcategories',
        'categoryNavDescription' => 'Jump into nested topics under this category.',
    ])
@endsection
