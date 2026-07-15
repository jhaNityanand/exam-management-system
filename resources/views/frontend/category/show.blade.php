@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => $category->meta_title ?: $category->name,
        'description' => $category->meta_description ?: ($category->description ?: 'Exams in '.$category->name),
        'keywords' => $category->meta_keywords,
        'canonical' => $category->canonical_url ?: url()->current(),
    ];
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Categories', 'url' => route('frontend.categories.index')],
                ['label' => $category->name],
            ]])
            <h1>{{ $category->name }}</h1>
            @if($category->description)
                <p>{{ $category->description }}</p>
            @endif
        </div>
    </div>

    <div class="et-container" style="padding:1.75rem 0 3rem;display:grid;gap:2rem">
        <section>
            @include('frontend.components.section-heading', [
                'title' => 'Exams',
                'subtitle' => '',
                'actionUrl' => route('frontend.exams.index', ['category_id' => $category->id]),
                'actionLabel' => 'View all',
            ])
            @if(($exams ?? collect())->isEmpty())
                @include('frontend.partials.empty-state', ['title' => 'No exams in this category', 'message' => ''])
            @else
                <div class="et-grid et-grid--3">
                    @foreach($exams as $exam)
                        @include('frontend.components.exam-card', ['exam' => $exam])
                    @endforeach
                </div>
            @endif
        </section>

        @if(($children ?? collect())->isNotEmpty())
            <section>
                @include('frontend.components.section-heading', ['title' => 'Subcategories', 'subtitle' => ''])
                <div class="et-grid et-grid--4">
                    @foreach($children as $child)
                        @include('frontend.components.category-card', ['category' => $child])
                    @endforeach
                </div>
            </section>
        @endif

        @if(($blogs ?? collect())->isNotEmpty())
            <section>
                @include('frontend.components.section-heading', ['title' => 'Related blogs', 'subtitle' => ''])
                <div class="et-grid et-grid--3">
                    @foreach($blogs as $blog)
                        @include('frontend.components.blog-card', ['blog' => $blog])
                    @endforeach
                </div>
            </section>
        @endif

        @if(($newsItems ?? $news ?? collect())->isNotEmpty())
            <section>
                @include('frontend.components.section-heading', ['title' => 'Related news', 'subtitle' => ''])
                <div class="et-grid et-grid--3">
                    @foreach(($newsItems ?? $news) as $item)
                        @include('frontend.components.news-card', ['news' => $item])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
