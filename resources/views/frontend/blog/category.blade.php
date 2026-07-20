@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => ($category->name ?? 'Category').' blogs',
        'description' => $category->description ?? 'Blog posts in this category.',
    ];
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Blogs', 'url' => route('frontend.blogs.index')],
                ['label' => $category->name ?? 'Category'],
            ]])
            <h1>{{ $category->name ?? 'Category' }}</h1>
            @if(!empty($category->description))
                <p>{{ $category->description }}</p>
            @endif
        </div>
    </div>

    <div class="et-container et-section">
        @if(($blogs ?? collect())->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No posts in this category', 'message' => ''])
        @else
            <div class="et-grid et-grid--3" data-load-more-list>
                @foreach($blogs as $blog)
                    @include('frontend.components.blog-card', ['blog' => $blog])
                @endforeach
            </div>
            @include('frontend.partials.load-more', [
                'paginator' => $blogs,
                'endpoint' => route('frontend.blogs.category', $category->slug).(($qs = request()->getQueryString()) ? '?'.$qs : ''),
            ])
        @endif
    </div>
@endsection
