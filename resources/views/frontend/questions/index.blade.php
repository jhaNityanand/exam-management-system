@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Questions',
        'description' => 'Browse practice questions, explanations, and categories on Examtube.',
    ];
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Questions'],
            ]])
            <h1>Questions</h1>
            <p>Latest practice questions with clear explanations — built like a modern knowledge base.</p>
            <div class="et-page-hero__actions">
                <a href="{{ route('frontend.questions.categories') }}" class="et-btn et-btn--soft et-btn--sm">Browse categories</a>
            </div>
        </div>
    </div>

    <div class="et-container et-section">
        <form class="et-filters" method="get" action="{{ route('frontend.questions.index') }}" data-load-more-filters>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search questions…">
            <select name="category" aria-label="Category">
                <option value="">All categories</option>
                @foreach(($categories ?? collect()) as $cat)
                    <option value="{{ $cat->slug }}" @selected(request('category') === $cat->slug)>{{ $cat->name }}</option>
                @endforeach
            </select>
            <select name="difficulty" aria-label="Difficulty">
                <option value="">All levels</option>
                @foreach(['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard', 'very_hard' => 'Very hard'] as $val => $label)
                    <option value="{{ $val }}" @selected(request('difficulty') === $val)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="sort" aria-label="Sort">
                <option value="latest" @selected(request('sort', 'latest') === 'latest')>Latest</option>
                <option value="popular" @selected(request('sort') === 'popular')>Popular</option>
                <option value="title" @selected(request('sort') === 'title')>Title</option>
                <option value="difficulty" @selected(request('sort') === 'difficulty')>Difficulty</option>
            </select>
            <button type="submit" class="et-btn et-btn--primary et-btn--sm">Apply</button>
        </form>

        @if(($questions ?? collect())->isEmpty())
            @include('frontend.partials.empty-state', [
                'title' => 'No questions found',
                'message' => 'Try another search or browse categories.',
            ])
        @else
            <div class="et-grid et-grid--3" data-load-more-list>
                @foreach($questions as $question)
                    @include('frontend.components.question-card', ['question' => $question])
                @endforeach
            </div>
            @include('frontend.partials.load-more', [
                'paginator' => $questions,
                'endpoint' => route('frontend.questions.index', request()->query()),
            ])
        @endif
    </div>
@endsection
