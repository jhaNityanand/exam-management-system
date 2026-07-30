@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Frequently Asked Questions',
        'description' => 'Find answers to common questions about exams, accounts, and using '.($siteBrand['name'] ?? config('app.name')).'.',
        'breadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'FAQs'],
        ],
    ];
@endphp

@section('content')
<section class="et-page-hero et-page-hero--help">
    <div class="et-container et-page-hero__inner">
        @include('frontend.partials.breadcrumbs', ['breadcrumbs' => $seo['breadcrumbs']])
        <p class="et-eyebrow">Help Center</p>
        <h1>Frequently Asked Questions</h1>
        <p class="et-page-hero__lead">Quick answers to help you prepare, attempt exams, and manage your account.</p>
    </div>
</section>

<section class="et-section et-faq-page">
    <div class="et-container et-faq-page__layout">
        @if(($faqs ?? collect())->isEmpty())
            @include('frontend.partials.empty-state', [
                'title' => 'No FAQs yet',
                'message' => 'Check back soon — we are preparing helpful answers.',
            ])
        @else
            <aside class="et-faq-page__tools">
                <label class="et-faq-search">
                    <span class="et-faq-search__label">Search FAQs</span>
                    <span class="et-faq-search__control">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                            <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <input type="search" data-faq-search placeholder="Type a keyword…" autocomplete="off">
                    </span>
                </label>

                @if(($groupedFaqs ?? collect())->count() > 1)
                    <nav class="et-faq-toc" aria-label="FAQ categories">
                        <p class="et-faq-toc__label">Categories</p>
                        <ul>
                            @foreach($groupedFaqs as $categoryName => $items)
                                <li>
                                    <a href="#faq-group-{{ \Illuminate\Support\Str::slug($categoryName) }}">
                                        <span>{{ $categoryName }}</span>
                                        <em>{{ $items->count() }}</em>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                @endif

                <div class="et-faq-page__cta">
                    <p>Still stuck?</p>
                    <a href="{{ url('/contact-us') }}" class="et-btn et-btn--primary et-btn--sm">Contact support</a>
                </div>
            </aside>

            <div class="et-faq-page__main" data-faq-root>
                @foreach(($groupedFaqs ?? collect()) as $categoryName => $items)
                    <section class="et-faq-group" id="faq-group-{{ \Illuminate\Support\Str::slug($categoryName) }}" data-faq-group>
                        <h2 class="et-faq-group__title">{{ $categoryName }}</h2>
                        @include('frontend.components.faq-accordion', ['faqs' => $items])
                    </section>
                @endforeach
                <p class="et-faq-page__empty" data-faq-empty hidden>No questions match your search.</p>
            </div>
        @endif
    </div>
</section>
@endsection
