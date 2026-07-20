@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'Question categories', 'description' => 'Browse question categories on Examtube.'];
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Questions', 'url' => route('frontend.questions.index')],
                ['label' => 'Categories'],
            ]])
            <h1>Question categories</h1>
            <p>Explore topics and jump into the latest practice questions.</p>
        </div>
    </div>

    <div class="et-container et-section">
        @if(($categories ?? collect())->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No categories yet', 'message' => ''])
        @else
            <div class="et-grid et-grid--3" data-load-more-list>
                @foreach($categories as $category)
                    @include('frontend.components.question-category-card', ['category' => $category])
                @endforeach
            </div>
            @include('frontend.partials.load-more', [
                'paginator' => $categories,
                'endpoint' => route('frontend.questions.categories', request()->query()),
            ])
        @endif
    </div>
@endsection
