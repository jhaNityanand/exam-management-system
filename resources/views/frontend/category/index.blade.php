@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'Categories', 'description' => 'Browse exams by category and stream.'];
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

    <div class="et-container" style="padding:1.5rem 0 3rem">
        @if(($categories ?? collect())->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No categories yet', 'message' => ''])
        @else
            <div class="et-grid et-grid--4">
                @foreach($categories as $category)
                    @include('frontend.components.category-card', ['category' => $category])
                @endforeach
            </div>
            @if(method_exists($categories, 'links'))
                <div class="et-pagination">{{ $categories->withQueryString()->links() }}</div>
            @endif
        @endif
    </div>
@endsection
