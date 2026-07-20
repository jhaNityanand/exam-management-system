@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Exams',
        'description' => $siteSettings['seo.default_description'] ?? 'Browse published exams and mock tests on Examtube.in.',
    ];
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Exams'],
            ]])
            <h1>Exams</h1>
            <p>Filter and practice with published mock tests.</p>
        </div>
    </div>

    <div class="et-container et-section">
        <form class="et-filters" method="get" action="{{ route('frontend.exams.index') }}" data-load-more-filters>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search exams…">
            <select name="category_id" aria-label="Category">
                <option value="">All categories</option>
                @foreach(($categories ?? collect()) as $cat)
                    <option value="{{ $cat->id }}" @selected((string) request('category_id') === (string) $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
            <select name="difficulty_level" aria-label="Difficulty">
                <option value="">All levels</option>
                @foreach(['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'] as $val => $label)
                    <option value="{{ $val }}" @selected(request('difficulty_level') === $val)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="sort" aria-label="Sort">
                <option value="latest" @selected(request('sort', 'latest') === 'latest')>Latest</option>
                <option value="title" @selected(request('sort') === 'title')>Title</option>
                <option value="duration" @selected(request('sort') === 'duration')>Duration</option>
            </select>
            <button type="submit" class="et-btn et-btn--primary et-btn--sm">Apply</button>
        </form>

        @if(($exams ?? collect())->isEmpty())
            @include('frontend.partials.empty-state', [
                'title' => 'No exams found',
                'message' => 'Try clearing filters or check back later.',
            ])
        @else
            <div class="et-grid et-grid--3" data-load-more-list>
                @foreach($exams as $exam)
                    @include('frontend.components.exam-card', ['exam' => $exam])
                @endforeach
            </div>
            @include('frontend.partials.load-more', [
                'paginator' => $exams,
                'endpoint' => route('frontend.exams.index', request()->query()),
            ])
        @endif
    </div>
@endsection
