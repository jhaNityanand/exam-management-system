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

    <div class="et-container" style="padding:1.5rem 0 3rem">
        <form class="et-filters" method="get" action="{{ route('frontend.blogs.index') }}">
            <input type="search" name="search" value="{{ request('search', request('q')) }}" placeholder="Search blogs…">
            <button type="submit" class="et-btn et-btn--primary et-btn--sm">Search</button>
        </form>

        @if(($blogs ?? collect())->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No blogs found', 'message' => 'New posts will show up here.'])
        @else
            <div class="et-grid et-grid--3">
                @foreach($blogs as $blog)
                    @include('frontend.components.blog-card', ['blog' => $blog])
                @endforeach
            </div>
            @if(method_exists($blogs, 'links'))
                <div class="et-pagination">{{ $blogs->withQueryString()->links() }}</div>
            @endif
        @endif
    </div>
@endsection
