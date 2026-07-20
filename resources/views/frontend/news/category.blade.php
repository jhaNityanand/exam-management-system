@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => ($category->name ?? 'Category').' news',
        'description' => $category->description ?? 'News in this category.',
    ];
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'News', 'url' => route('frontend.news.index')],
                ['label' => $category->name ?? 'Category'],
            ]])
            <h1>{{ $category->name ?? 'Category' }}</h1>
            @if(!empty($category->description))
                <p>{{ $category->description }}</p>
            @endif
        </div>
    </div>

    <div class="et-container et-section">
        @if(($news ?? collect())->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No news in this category', 'message' => ''])
        @else
            <div class="et-grid et-grid--3" data-load-more-list>
                @foreach($news as $item)
                    @include('frontend.components.news-card', ['news' => $item])
                @endforeach
            </div>
            @include('frontend.partials.load-more', [
                'paginator' => $news,
                'endpoint' => route('frontend.news.category', $category->slug).(($qs = request()->getQueryString()) ? '?'.$qs : ''),
            ])
        @endif
    </div>
@endsection
