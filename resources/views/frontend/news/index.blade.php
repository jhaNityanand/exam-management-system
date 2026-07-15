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
                <div style="margin-top:1rem">
                    <a href="{{ route('frontend.news.trending') }}" class="et-btn et-btn--soft et-btn--sm">Trending now</a>
                </div>
            @endif
        </div>
    </div>

    <div class="et-container" style="padding:1.5rem 0 3rem">
        <form class="et-filters" method="get" action="{{ route('frontend.news.index') }}">
            <input type="search" name="search" value="{{ request('search', request('q')) }}" placeholder="Search news…">
            <label style="display:inline-flex;align-items:center;gap:.35rem;font-size:.85rem;font-weight:600;color:var(--et-text-muted)">
                <input type="checkbox" name="breaking" value="1" @checked(request()->boolean('breaking'))> Breaking
            </label>
            <label style="display:inline-flex;align-items:center;gap:.35rem;font-size:.85rem;font-weight:600;color:var(--et-text-muted)">
                <input type="checkbox" name="trending" value="1" @checked(request()->boolean('trending'))> Trending
            </label>
            <button type="submit" class="et-btn et-btn--primary et-btn--sm">Filter</button>
        </form>

        @if(($newsItems ?? $news ?? collect())->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No news found', 'message' => ''])
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
