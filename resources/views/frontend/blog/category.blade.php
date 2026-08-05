@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => ($category->name ?? 'Category').' blogs',
        'description' => $category->description ?? 'Blog posts in this category.',
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
        'listingEndpoint' => route('frontend.blogs.category', $category->slug),
        'listingLoadMoreEndpoint' => route('frontend.blogs.category', $category->slug).(request()->getQueryString() ? '?'.request()->getQueryString() : ''),
        'listingModalId' => 'et-blog-category-filter-modal',
        'listingTitle' => 'Filter blogs',
        'listingHeading' => $category->name ?? 'Category',
        'listingLead' => $category->description ?? null,
        'listingBreadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Blogs', 'url' => route('frontend.blogs.index')],
            ['label' => $category->name ?? 'Category'],
        ],
        'listingSearchLabel' => 'Search blog',
        'listingSearchPlaceholder' => 'Search by title or topic…',
        'listingItems' => $blogs,
        'listingCard' => 'frontend.components.blog-card',
        'listingCardKey' => 'blog',
        'listingEmptyTitle' => 'No posts in this category',
        'listingResetUrl' => route('frontend.blogs.category', $category->slug),
        'activeFilterCount' => $activeFilterCount,
        'listingGridClass' => 'et-grid et-grid--3',
        'listingSkeletonCount' => 6,
        'categoryNav' => $categoryNav ?? null,
        'categoryNavTitle' => 'Subcategories',
        'categoryNavDescription' => 'Jump into nested topics under this category.',
    ])
@endsection
