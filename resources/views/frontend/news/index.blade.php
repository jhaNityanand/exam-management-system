@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'News', 'description' => 'Education news, alerts, and trending updates for aspirants.'];
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'News'],
            ]])
            <h1>News</h1>
            <p>Breaking alerts and trending updates for candidates.</p>
            @if(Route::has('frontend.news.trending'))
                <div class="et-page-hero__actions">
                    <a href="{{ route('frontend.news.trending') }}" class="et-btn et-btn--soft et-btn--sm">Trending now</a>
                </div>
            @endif
        </div>
    </div>

    <div class="et-container et-section">
        <form class="et-filters" method="get" action="{{ route('frontend.news.index') }}" data-load-more-filters>
            <input type="search" name="search" value="{{ request('search', request('q')) }}" placeholder="Search news…">
            <label class="et-filter-check">
                <input type="checkbox" name="breaking" value="1" @checked(request()->boolean('breaking'))> Breaking
            </label>
            <label class="et-filter-check">
                <input type="checkbox" name="trending" value="1" @checked(request()->boolean('trending'))> Trending
            </label>
            <button type="submit" class="et-btn et-btn--primary et-btn--sm">Filter</button>
        </form>

        @if(($newsItems ?? $news ?? collect())->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No news found', 'message' => ''])
        @else
            <div class="et-grid et-grid--3" data-load-more-list>
                @foreach(($newsItems ?? $news) as $item)
                    @include('frontend.components.news-card', ['news' => $item])
                @endforeach
            </div>
            @php $paginator = $newsItems ?? $news; @endphp
            @include('frontend.partials.load-more', [
                'paginator' => $paginator,
                'endpoint' => route('frontend.news.index', request()->query()),
            ])
        @endif
    </div>
@endsection
