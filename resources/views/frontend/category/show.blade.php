@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => $category->meta_title ?: $category->name,
        'description' => $category->meta_description ?: ($category->description ?: 'Exams in '.$category->name),
        'keywords' => $category->meta_keywords,
        'canonical' => $category->canonical_url ?: url()->current(),
        'og_title' => $category->og_title,
        'og_description' => $category->og_description,
        'image' => $category->seoImageUrl(),
        'image_type' => 'category',
    ];
    $relatedBlogs = $relatedBlogs ?? collect();
    $relatedNews = $relatedNews ?? collect();
    $hasCategoryNav = ! empty($categoryNav['roots'] ?? null);
@endphp

@section('content')
<div class="et-page-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Categories', 'url' => route('frontend.categories.index')],
                ['label' => $category->name],
            ]])
            @include('frontend.partials.ad-placement', ['page' => 'category_detail', 'position' => 'above_title'])
            <h1>{{ $category->name }}</h1>
            @if($category->description)
                <p>{{ $category->description }}</p>
            @endif
            @include('frontend.partials.ad-placement', ['page' => 'category_detail', 'position' => 'below_title'])
        </div>
    </div>

<div class="et-container et-section">
        <div class="et-listing__layout{{ $hasCategoryNav ? ' et-listing__layout--with-nav' : '' }}">
            <div class="et-listing__main et-stack-lg">
                <section>
                    @include('frontend.components.section-heading', [
                        'title' => 'Exams',
                        'subtitle' => '',
                        'actionUrl' => route('frontend.exams.index', ['category_id' => $category->id]),
                        'actionLabel' => 'View all',
                    ])
                    @if(($exams ?? collect())->isEmpty())
                        @include('frontend.partials.empty-state', [
                            'title' => 'No exams in this category',
                            'message' => 'Browse all published exams or try another category.',
                            'actionUrl' => route('frontend.exams.index'),
                            'actionLabel' => 'Browse exams',
                        ])
                    @else
                        <div class="et-grid et-grid--3" data-load-more-list>
                            @foreach($exams as $exam)
                                @include('frontend.components.exam-card', ['exam' => $exam])
                            @endforeach
                        </div>
                        @include('frontend.partials.load-more', [
                            'paginator' => $exams,
                            'endpoint' => route('frontend.categories.show', $category).(($qs = request()->getQueryString()) ? '?'.$qs : ''),
                        ])
                    @endif
                </section>
                @include('frontend.partials.ad-placement', ['page' => 'category_detail', 'position' => 'between_sections'])

                @if($relatedBlogs->isNotEmpty())
                    <section>
                        @include('frontend.components.section-heading', ['title' => 'Related blogs', 'subtitle' => ''])
                        <div class="et-grid et-grid--3">
                            @foreach($relatedBlogs as $blog)
                                @include('frontend.components.blog-card', ['blog' => $blog])
                            @endforeach
                        </div>
                    </section>
                    @include('frontend.partials.ad-placement', ['page' => 'category_detail', 'position' => 'after_blogs'])
                @endif

                @if($relatedNews->isNotEmpty())
                    <section>
                        @include('frontend.components.section-heading', ['title' => 'Related news', 'subtitle' => ''])
                        <div class="et-grid et-grid--3">
                            @foreach($relatedNews as $item)
                                @include('frontend.components.news-card', ['news' => $item])
                            @endforeach
                        </div>
                    </section>
                    @include('frontend.partials.ad-placement', ['page' => 'category_detail', 'position' => 'after_content'])
                @endif
            </div>

            @if($hasCategoryNav)
                <div class="et-listing__aside">
                    @include('frontend.partials.category-nav', [
                        'categoryNav' => $categoryNav,
                        'categoryNavTitle' => 'Subcategories',
                        'categoryNavDescription' => 'Jump into nested topics under this category.',
                        'adPage' => 'category_detail',
                    ])
                </div>
            @endif
        </div>
    </div>

@endsection
