@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Exams',
        'description' => $siteSettings['seo.default_description'] ?? 'Browse published exams and mock tests on Examtube.in.',
        'image_type' => 'exam',
        'breadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Exams'],
        ],
    ];

    $examModes = [
        'standard' => 'Standard',
        'practice' => 'Practice',
        'proctored' => 'Proctored',
    ];

    $activeFilterCount = collect([
        request('search'),
        request('category_id'),
        request('difficulty_level'),
        request('exam_mode'),
        request('pricing'),
        request('sort') && request('sort') !== 'latest' ? request('sort') : null,
    ])->filter(fn ($v) => filled($v))->count();
@endphp

@section('content')
<x-ad-layout page="exam_list">
    <div class="et-listing et-listing--stack" data-listing data-endpoint="{{ route('frontend.exams.index') }}">
        <div class="et-page-hero et-page-hero--listing">
            <div class="et-container">
                @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                    ['label' => 'Home', 'url' => route('home')],
                    ['label' => 'Exams'],
                ]])
                <div class="et-page-hero__row">
                    <div class="et-page-hero__copy">
                        <h1>Exams</h1>
                        <p>Discover timed mocks, practice papers, and proctored assessments built for serious aspirants.</p>
                    </div>
                    <div class="et-filter-toolbar">
                        <button type="button" class="et-btn et-btn--soft et-filter-trigger" data-filter-open aria-haspopup="dialog" aria-controls="et-exam-filter-modal">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 5h16M7 12h10M10 19h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>Filters</span>
                            <span class="et-filter-trigger__count" data-filter-count @if($activeFilterCount < 1) hidden @endif>{{ $activeFilterCount }}</span>
                        </button>

                        <button
                            type="button"
                            class="et-btn et-btn--ghost et-filter-reset"
                            data-filters-reset
                            @if($activeFilterCount < 1) hidden @endif
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="et-container et-section">
            <div class="et-filter-modal" id="et-exam-filter-modal" data-filter-modal hidden>
            <div class="et-filter-modal__backdrop" data-filter-close tabindex="-1"></div>
            <div class="et-filter-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="et-exam-filter-title">
                <div class="et-filter-modal__head">
                    <h2 id="et-exam-filter-title">Filter exams</h2>
                    <button type="button" class="et-icon-btn" data-filter-close aria-label="Close filters">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>

                <form
                    class="et-filters et-filters--modal"
                    method="get"
                    action="{{ route('frontend.exams.index') }}"
                    data-load-more-filters
                >
                    <label class="et-field">
                        <span class="et-field__label">Search exam</span>
                        <span class="et-field__control">
                            <svg class="et-field__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                                <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <input type="text" name="search" enterkeyhint="search" inputmode="search" autocomplete="off" value="{{ request('search') }}" placeholder="Search by title or topic…">
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
                        <span class="et-field__label">Difficulty</span>
                        <span class="et-field__control">
                            <svg class="et-field__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3l2.2 6.6H21l-5.4 4 2.1 6.4L12 16.8 6.3 20l2.1-6.4L3 9.6h6.8L12 3z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            </svg>
                            <select name="difficulty_level" aria-label="Difficulty" data-placeholder="Select difficulty">
                                <option value="">All levels</option>
                                @foreach(['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'] as $val => $label)
                                    <option value="{{ $val }}" @selected(request('difficulty_level') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </span>
                    </label>

                    <label class="et-field">
                        <span class="et-field__label">Exam type</span>
                        <span class="et-field__control">
                            <svg class="et-field__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <rect x="4" y="4" width="16" height="16" rx="3" stroke="currentColor" stroke-width="2"/>
                                <path d="M8 9h8M8 12h8M8 15h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <select name="exam_mode" aria-label="Exam type" data-placeholder="Select exam type">
                                <option value="">All types</option>
                                @foreach($examModes as $val => $label)
                                    <option value="{{ $val }}" @selected(request('exam_mode') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </span>
                    </label>

                    <label class="et-field">
                        <span class="et-field__label">Pricing</span>
                        <span class="et-field__control">
                            <svg class="et-field__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/>
                                <path d="M12 8v8M9.5 10.5c.6-1 1.5-1.5 2.5-1.5s1.8.5 2.2 1.3c.4.8-.1 1.7-1.7 2.2-1.6.5-2.2 1.2-2.2 2.2 0 .9.9 1.8 2.7 1.8s2.3-.7 2.7-1.3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                            <select name="pricing" aria-label="Pricing" data-placeholder="Select pricing">
                                <option value="">Free &amp; Paid</option>
                                <option value="free" @selected(request('pricing') === 'free')>Free</option>
                                <option value="paid" @selected(request('pricing') === 'paid')>Paid</option>
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
                                <option value="duration" @selected(request('sort') === 'duration')>Duration</option>
                                <option value="difficulty" @selected(request('sort') === 'difficulty')>Difficulty</option>
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

        <div class="et-listing__layout{{ ! empty($categoryNav['roots'] ?? null) ? ' et-listing__layout--with-nav' : '' }}">
        <div class="et-listing__main" data-listing-main>
            <div class="et-listing__skeleton et-grid et-grid--3" data-listing-skeleton hidden aria-hidden="true">
                @for($i = 0; $i < 6; $i++)
                    @include('frontend.partials.skeleton-card')
                @endfor
            </div>

            <div class="et-listing__empty" data-listing-empty @if(($exams ?? collect())->isNotEmpty()) hidden @endif>
                @include('frontend.partials.empty-state', [
                    'title' => 'No exams found',
                    'message' => 'Try adjusting or resetting your filters to discover more practice papers.',
                    'actionUrl' => route('frontend.exams.index'),
                    'actionLabel' => 'Reset filters',
                ])
            </div>

            <div class="et-grid et-grid--3" data-load-more-list @if(($exams ?? collect())->isEmpty()) hidden @endif>
                @foreach($exams ?? [] as $exam)
                    @include('frontend.components.exam-card', ['exam' => $exam])
                @endforeach
            </div>

            <x-ad-slot page="exam_list" position="below_items" />

            <div data-load-more-slot>
                @include('frontend.partials.load-more', [
                    'paginator' => $exams,
                    'endpoint' => route('frontend.exams.index', request()->query()),
                ])
            </div>

            <x-ad-slot page="exam_list" position="after_content" />
        </div>

        @if(! empty($categoryNav['roots'] ?? null))
            <div class="et-listing__aside">
                @include('frontend.partials.category-nav', [
                    'categoryNav' => $categoryNav,
                    'categoryNavTitle' => 'Categories',
                    'categoryNavDescription' => 'Browse exam topics and jump into related assessments.',
                ])
            </div>
        @endif
        </div>
        </div>
    </div>
</x-ad-layout>
@endsection
