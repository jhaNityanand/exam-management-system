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

    <div class="et-container" style="padding:1.5rem 0 3rem">
        @if(($newsItems ?? $news ?? collect())->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No news in this category', 'message' => ''])
        @else
            <div class="et-grid et-grid--3">
                @foreach(($newsItems ?? $news) as $item)
                    @include('frontend.components.news-card', ['news' => $item])
                @endforeach
            </div>
            @php $paginator = $newsItems ?? $news; @endphp
            @if(method_exists($paginator, 'links'))
                <div class="et-pagination">{{ $paginator->withQueryString()->links() }}</div>
            @endif
        @endif
    </div>
@endsection
