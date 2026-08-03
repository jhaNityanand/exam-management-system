@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Categories',
        'description' => 'Browse exams, blogs, and news by category.',
        'image_type' => 'category',
        'breadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Categories'],
        ],
    ];

    $activeFilterCount = collect([
        request('search'),
        request('type'),
        request('sort') && request('sort') !== 'name' ? request('sort') : null,
    ])->filter(fn ($v) => filled($v))->count();
@endphp

@section('content')
<x-ad-layout page="categories">
    <div class="et-listing et-listing--stack" data-listing data-endpoint="{{ route('frontend.categories.index') }}">
        <div class="et-page-hero et-page-hero--listing">
            <div class="et-container">
                @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                    ['label' => 'Home', 'url' => route('home')],
                    ['label' => 'Categories'],
                ]])
                <div class="et-page-hero__row">
                    <div class="et-page-hero__copy">
                        <h1>Categories</h1>
                        <p>Find exams and learning paths by competitive stream.</p>
                    </div>
                    <div class="et-filter-toolbar">
                        <button type="button" class="et-btn et-btn--soft et-filter-trigger" data-filter-open aria-haspopup="dialog" aria-controls="et-category-filter-modal">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 5h16M7 12h10M10 19h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>Filters</span>
                            <span class="et-filter-trigger__count" data-filter-count @if($activeFilterCount < 1) hidden @endif>{{ $activeFilterCount }}</span>
                        </button>
                        <button type="button" class="et-btn et-btn--ghost et-filter-reset" data-filters-reset @if($activeFilterCount < 1) hidden @endif>Reset</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="et-container et-section">
            <div class="et-filter-modal" id="et-category-filter-modal" data-filter-modal hidden>
                <div class="et-filter-modal__backdrop" data-filter-close tabindex="-1"></div>
                <div class="et-filter-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="et-category-filter-title">
                    <div class="et-filter-modal__head">
                        <h2 id="et-category-filter-title">Filter categories</h2>
                        <button type="button" class="et-icon-btn" data-filter-close aria-label="Close filters">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>

                    <form class="et-filters et-filters--modal" method="get" action="{{ route('frontend.categories.index') }}" data-load-more-filters data-default-sort="name">
                        <label class="et-field">
                            <span class="et-field__label">Search category</span>
                            <span class="et-field__control">
                                <svg class="et-field__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                                    <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                <input type="text" name="search" enterkeyhint="search" inputmode="search" autocomplete="off" value="{{ request('search') }}" placeholder="Search by name…">
                            </span>
                        </label>

                        <label class="et-field">
                            <span class="et-field__label">Type</span>
                            <span class="et-field__control">
                                <svg class="et-field__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 7h6l2-2h8v14H4V7z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                </svg>
                                <select name="type" aria-label="Type">
                                    <option value="">All types</option>
                                    <option value="exams" @selected(request('type') === 'exams')>Exam categories</option>
                                    <option value="blogs" @selected(request('type') === 'blogs')>Blog categories</option>
                                    <option value="news" @selected(request('type') === 'news')>News categories</option>
                                </select>
                            </span>
                        </label>

                        <label class="et-field">
                            <span class="et-field__label">Sort by</span>
                            <span class="et-field__control">
                                <svg class="et-field__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M8 7h12M8 12h8M8 17h4M4 7h.01M4 12h.01M4 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                <select name="sort" aria-label="Sort">
                                    <option value="name" @selected(request('sort', 'name') === 'name')>Name A–Z</option>
                                    <option value="name_desc" @selected(request('sort') === 'name_desc')>Name Z–A</option>
                                </select>
                            </span>
                        </label>

                        <div class="et-filter-modal__actions">
                            <button type="button" class="et-btn et-btn--ghost" data-filter-close>Cancel</button>
                            <button type="submit" class="et-btn et-btn--primary">Apply filters</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="et-listing__main" data-listing-main>
                <div class="et-listing__skeleton et-grid et-grid--4" data-listing-skeleton hidden aria-hidden="true">
                    @for($i = 0; $i < 8; $i++)
                        @include('frontend.partials.skeleton-card')
                    @endfor
                </div>

                <div class="et-listing__empty" data-listing-empty @if(($categories ?? collect())->isNotEmpty()) hidden @endif>
                    @include('frontend.partials.empty-state', [
                        'title' => 'No categories found',
                        'message' => 'Try adjusting or resetting your filters.',
                        'actionUrl' => route('frontend.categories.index'),
                        'actionLabel' => 'Reset filters',
                    ])
                </div>

                <div class="et-grid et-grid--4" data-load-more-list @if(($categories ?? collect())->isEmpty()) hidden @endif>
                    @foreach($categories ?? [] as $item)
                        @include('frontend.components.catalog-category-card', ['item' => $item])
                    @endforeach
                </div>

                <x-ad-slot page="categories" position="below_items" />

                <div data-load-more-slot>
                    @include('frontend.partials.load-more', [
                        'paginator' => $categories,
                        'endpoint' => route('frontend.categories.index', request()->query()),
                    ])
                </div>

                <x-ad-slot page="categories" position="after_content" />
            </div>
        </div>
    </div>
</x-ad-layout>
@endsection
