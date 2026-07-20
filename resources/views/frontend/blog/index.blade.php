@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'Blogs', 'description' => 'Study strategies, mentor tips, and exam preparation guides.'];
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Blogs'],
            ]])
            <h1>Blogs</h1>
            <p>Practical preparation guides from mentors and educators.</p>
        </div>
    </div>

    <div class="et-container et-section">
        <form class="et-filters" method="get" action="{{ route('frontend.blogs.index') }}" data-load-more-filters>
            <input type="search" name="search" value="{{ request('search', request('q')) }}" placeholder="Search blogs…">
            <button type="submit" class="et-btn et-btn--primary et-btn--sm">Search</button>
        </form>

        @if(($blogs ?? collect())->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No blogs found', 'message' => 'New posts will show up here.'])
        @else
            <div class="et-grid et-grid--3" data-load-more-list>
                @foreach($blogs as $blog)
                    @include('frontend.components.blog-card', ['blog' => $blog])
                @endforeach
            </div>
            @include('frontend.partials.load-more', [
                'paginator' => $blogs,
                'endpoint' => route('frontend.blogs.index', request()->query()),
            ])
        @endif
    </div>
@endsection
