@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => $category->meta_title ?: $category->name,
        'description' => $category->meta_description ?: ($category->description ?: 'Questions in '.$category->name),
        'image' => $category->seoImageUrl(),
        'image_type' => 'category',
    ];
    $activeFilterCount = collect([
        request('search'),
        request('difficulty'),
        request('sort') && request('sort') !== 'latest' ? request('sort') : null,
    ])->filter(fn ($v) => filled($v))->count();

    $extraFields = view('frontend.partials.listing-difficulty-field')->render();
@endphp

@section('content')
    @include('frontend.partials.listing-page', [
        'adPage' => 'question_list',
        'listingEndpoint' => route('frontend.questions.category', $category->slug),
        'listingLoadMoreEndpoint' => route('frontend.questions.category', $category->slug).(request()->getQueryString() ? '?'.request()->getQueryString() : ''),
        'listingModalId' => 'et-question-category-filter-modal',
        'listingTitle' => 'Filter questions',
        'listingHeading' => $category->name,
        'listingLead' => $category->description ?: 'Latest questions in this category.',
        'listingHeroSuffix' => '<p class="et-card__meta">'.(int) ($category->questions_count ?? 0).' questions</p>',
        'listingBreadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Questions', 'url' => route('frontend.questions.index')],
            ['label' => 'Categories', 'url' => route('frontend.questions.categories')],
            ['label' => $category->name],
        ],
        'listingSearchLabel' => 'Search question',
        'listingSearchPlaceholder' => 'Search by title or topic…',
        'listingExtraFields' => $extraFields,
        'listingSortOptions' => [
            'latest' => 'Latest',
            'popular' => 'Popular',
            'title' => 'Title A–Z',
            'difficulty' => 'Difficulty',
        ],
        'listingItems' => $questions,
        'listingCard' => 'frontend.components.question-card',
        'listingCardKey' => 'question',
        'listingEmptyTitle' => 'No questions in this category',
        'listingResetUrl' => route('frontend.questions.category', $category->slug),
        'activeFilterCount' => $activeFilterCount,
        'listingGridClass' => 'et-grid et-grid--3',
        'listingSkeletonCount' => 6,
        'categoryNav' => $categoryNav ?? null,
        'categoryNavTitle' => 'Subcategories',
        'categoryNavDescription' => 'Jump into nested topics under this category.',
    ])
@endsection
