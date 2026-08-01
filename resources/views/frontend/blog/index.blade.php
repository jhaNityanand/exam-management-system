@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Blogs',
        'description' => 'Study strategies, mentor tips, and exam preparation guides.',
        'image_type' => 'blog',
        'breadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Blogs'],
        ],
    ];

    $activeFilterCount = collect([
        request('search', request('q')),
        request('category_id'),
        request('sort') && request('sort') !== 'latest' ? request('sort') : null,
    ])->filter(fn ($v) => filled($v))->count();
@endphp

@section('content')
    <div class="et-listing et-listing--stack" data-listing data-endpoint="{{ route('frontend.blogs.index') }}">
        <div class="et-page-hero et-page-hero--listing">
            <div class="et-container">
                @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                    ['label' => 'Home', 'url' => route('home')],
                    ['label' => 'Blogs'],
                ]])
                <div class="et-page-hero__row">
                    <div class="et-page-hero__copy">
                        <h1>Blogs</h1>
                        <p>Practical preparation guides from mentors and educators.</p>
                    </div>
                    <div class="et-filter-toolbar">
                        <button type="button" class="et-btn et-btn--soft et-filter-trigger" data-filter-open aria-haspopup="dialog" aria-controls="et-blog-filter-modal">
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
            <div class="et-filter-modal" id="et-blog-filter-modal" data-filter-modal hidden>
                <div class="et-filter-modal__backdrop" data-filter-close tabindex="-1"></div>
                <div class="et-filter-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="et-blog-filter-title">
                    <div class="et-filter-modal__head">
                        <h2 id="et-blog-filter-title">Filter blogs</h2>
                        <button type="button" class="et-icon-btn" data-filter-close aria-label="Close filters">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>

                    <form class="et-filters et-filters--modal" method="get" action="{{ route('frontend.blogs.index') }}" data-load-more-filters>
                        <label class="et-field">
                            <span class="et-field__label">Search blog</span>
                            <span class="et-field__control">
                                <svg class="et-field__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                                    <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                <input type="search" name="search" value="{{ request('search', request('q')) }}" placeholder="Search by title or topic…">
                            </span>
                        </label>

                        <label class="et-field">
                            <span class="et-field__label">Category</span>
                            <span class="et-field__control">
                                <svg class="et-field__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 7h6l2-2h8v14H4V7z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                </svg>
                                <select name="category_id" aria-label="Category" data-placeholder="Select category">
                                    <option value="">All categories</option>
                                    @foreach(($categories ?? collect()) as $cat)
                                        <option value="{{ $cat->id }}" @selected((string) request('category_id') === (string) $cat->id)>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </span>
                        </label>

                        <label class="et-field">
                            <span class="et-field__label">Sort by</span>
                            <span class="et-field__control">
                                <svg class="et-field__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M8 7h12M8 12h8M8 17h4M4 7h.01M4 12h.01M4 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                <select name="sort" aria-label="Sort" data-placeholder="Select sort order">
                                    <option value="latest" @selected(request('sort', 'latest') === 'latest')>Latest</option>
                                    <option value="oldest" @selected(request('sort') === 'oldest')>Oldest</option>
                                    <option value="title" @selected(request('sort') === 'title')>Title A–Z</option>
                                    <option value="popular" @selected(request('sort') === 'popular')>Most viewed</option>
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
                <div class="et-listing__skeleton et-grid et-grid--3" data-listing-skeleton hidden aria-hidden="true">
                    @for($i = 0; $i < 6; $i++)
                        @include('frontend.partials.skeleton-card')
                    @endfor
                </div>

                <div class="et-listing__empty" data-listing-empty @if(($blogs ?? collect())->isNotEmpty()) hidden @endif>
                    @include('frontend.partials.empty-state', [
                        'title' => 'No blogs found',
                        'message' => 'Try adjusting or resetting your filters to discover more guides.',
                        'actionUrl' => route('frontend.blogs.index'),
                        'actionLabel' => 'Reset filters',
                    ])
                </div>

                <div class="et-grid et-grid--3" data-load-more-list @if(($blogs ?? collect())->isEmpty()) hidden @endif>
                    @foreach($blogs ?? [] as $blog)
                        @include('frontend.components.blog-card', ['blog' => $blog])
                    @endforeach
                </div>

                <div data-load-more-slot>
                    @include('frontend.partials.load-more', [
                        'paginator' => $blogs,
                        'endpoint' => route('frontend.blogs.index', request()->query()),
                    ])
                </div>
            </div>
        </div>
    </div>
@endsection
