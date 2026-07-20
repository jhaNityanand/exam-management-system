@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'Categories', 'description' => 'Browse exams, blogs, and news by category.'];
    $examCategories = $examCategories ?? collect();
    $blogCategories = $blogCategories ?? collect();
    $newsCategories = $newsCategories ?? collect();
    $hasAny = $examCategories->isNotEmpty() || $blogCategories->isNotEmpty() || $newsCategories->isNotEmpty();
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Categories'],
            ]])
            <h1>Categories</h1>
            <p>Find exams and learning paths by competitive stream.</p>
        </div>
    </div>

    <div class="et-container et-section" style="display:grid;gap:2.5rem">
        @unless($hasAny)
            @include('frontend.partials.empty-state', ['title' => 'No categories yet', 'message' => ''])
        @endunless

        @if($examCategories->isNotEmpty())
            <section>
                @include('frontend.components.section-heading', [
                    'title' => 'Exam categories',
                    'subtitle' => 'Practice by stream and subject.',
                    'actionUrl' => route('frontend.exams.index'),
                    'actionLabel' => 'All exams',
                ])
                <div class="et-grid et-grid--4">
                    @foreach($examCategories as $category)
                        @include('frontend.components.category-card', ['category' => $category])
                    @endforeach
                </div>
            </section>
        @endif

        @if($blogCategories->isNotEmpty())
            <section>
                @include('frontend.components.section-heading', [
                    'title' => 'Blog categories',
                    'subtitle' => 'Guides and preparation tips.',
                    'actionUrl' => route('frontend.blogs.index'),
                    'actionLabel' => 'All blogs',
                ])
                <div class="et-grid et-grid--4">
                    @foreach($blogCategories as $category)
                        <a href="{{ route('frontend.blogs.category', $category->slug) }}" class="et-category-card">
                            <div class="et-category-card__icon" aria-hidden="true">{{ strtoupper(mb_substr($category->name, 0, 1)) }}</div>
                            <h3>{{ $category->name }}</h3>
                            @if($category->description)
                                <p>{{ \Illuminate\Support\Str::limit($category->description, 90) }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if($newsCategories->isNotEmpty())
            <section>
                @include('frontend.components.section-heading', [
                    'title' => 'News categories',
                    'subtitle' => 'Alerts and exam updates.',
                    'actionUrl' => route('frontend.news.index'),
                    'actionLabel' => 'All news',
                ])
                <div class="et-grid et-grid--4">
                    @foreach($newsCategories as $category)
                        <a href="{{ route('frontend.news.category', $category->slug) }}" class="et-category-card">
                            <div class="et-category-card__icon" aria-hidden="true">{{ strtoupper(mb_substr($category->name, 0, 1)) }}</div>
                            <h3>{{ $category->name }}</h3>
                            @if($category->description)
                                <p>{{ \Illuminate\Support\Str::limit($category->description, 90) }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
