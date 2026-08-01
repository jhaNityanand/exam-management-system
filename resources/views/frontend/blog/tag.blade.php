@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => '#'.($tag->name ?? 'tag'),
        'description' => 'Blog posts tagged '.($tag->name ?? '').'.',
        'image_type' => 'blog',
    ];
    $activeFilterCount = collect([
        request('search'),
        request('sort') && request('sort') !== 'latest' ? request('sort') : null,
    ])->filter(fn ($v) => filled($v))->count();
@endphp

@section('content')
    @include('frontend.partials.listing-page', [
        'listingEndpoint' => route('frontend.blogs.tag', $tag->slug),
        'listingLoadMoreEndpoint' => route('frontend.blogs.tag', $tag->slug).(request()->getQueryString() ? '?'.request()->getQueryString() : ''),
        'listingModalId' => 'et-blog-tag-filter-modal',
        'listingTitle' => 'Filter blogs',
        'listingHeading' => '#'.($tag->name ?? 'tag'),
        'listingLead' => $tag->description ?? null,
        'listingBreadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Blogs', 'url' => route('frontend.blogs.index')],
            ['label' => '#'.($tag->name ?? 'tag')],
        ],
        'listingSearchLabel' => 'Search blog',
        'listingSearchPlaceholder' => 'Search by title or topic…',
        'listingItems' => $blogs,
        'listingCard' => 'frontend.components.blog-card',
        'listingCardKey' => 'blog',
        'listingEmptyTitle' => 'No posts with this tag',
        'listingResetUrl' => route('frontend.blogs.tag', $tag->slug),
        'activeFilterCount' => $activeFilterCount,
    ])
@endsection
