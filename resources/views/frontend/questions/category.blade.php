@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => $category->meta_title ?: $category->name,
        'description' => $category->meta_description ?: ($category->description ?: 'Questions in '.$category->name),
    ];
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Questions', 'url' => route('frontend.questions.index')],
                ['label' => 'Categories', 'url' => route('frontend.questions.categories')],
                ['label' => $category->name],
            ]])
            <div class="et-category-hero">
                <div class="et-category-card__icon" aria-hidden="true">
                    @if($category->image_path)
                        <img src="{{ asset($category->image_path) }}" alt="" width="56" height="56">
                    @else
                        <span>{{ strtoupper(mb_substr($category->name, 0, 1)) }}</span>
                    @endif
                </div>
                <div>
                    <h1>{{ $category->name }}</h1>
                    <p>{{ $category->description ?: 'Latest questions in this category.' }}</p>
                    <p class="et-card__meta">{{ (int) ($category->questions_count ?? 0) }} questions</p>
                </div>
            </div>
        </div>
    </div>

    <div class="et-container et-section">
        @if(($questions ?? collect())->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No questions in this category', 'message' => ''])
        @else
            <div class="et-grid et-grid--3" data-load-more-list>
                @foreach($questions as $question)
                    @include('frontend.components.question-card', ['question' => $question])
                @endforeach
            </div>
            @include('frontend.partials.load-more', [
                'paginator' => $questions,
                'endpoint' => route('frontend.questions.category', array_merge([$category->slug], request()->query())),
            ])
        @endif
    </div>
@endsection
