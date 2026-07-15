@extends('frontend.layouts.app')

@php
    $q = $q ?? request('q', '');
    $seo = [
        'title' => $q !== '' ? 'Search: '.$q : 'Search',
        'description' => 'Search exams, blogs, news, and categories on Examtube.in.',
    ];
    $results = $results ?? [];
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Search'],
            ]])
            <h1>Search</h1>
            <p>Find exams, blogs, news, and categories.</p>
            <form class="et-hero__search" style="margin-top:1rem;max-width:560px" method="get" action="{{ route('frontend.search') }}">
                <input type="search" name="q" value="{{ $q }}" placeholder="What are you preparing for?" autofocus>
                <button type="submit" class="et-btn et-btn--primary et-btn--sm">Search</button>
            </form>
        </div>
    </div>

    <div class="et-container et-search-results" style="padding-top:1.5rem">
        @if($q === '')
            @include('frontend.partials.empty-state', [
                'title' => 'Start searching',
                'message' => 'Enter a keyword to see exams, blogs, and news.',
            ])
        @else
            @php
                $exams = $results['exams'] ?? collect();
                $blogs = $results['blogs'] ?? collect();
                $newsItems = $results['news'] ?? collect();
                $categories = $results['categories'] ?? collect();
                $hasAny = collect([$exams, $blogs, $newsItems, $categories])->contains(fn ($c) => count($c));
            @endphp

            @unless($hasAny)
                @include('frontend.partials.empty-state', [
                    'title' => 'No results for “'.$q.'”',
                    'message' => 'Try a broader keyword or browse categories.',
                    'actionUrl' => route('frontend.categories.index'),
                    'actionLabel' => 'Browse categories',
                ])
            @else
                @if(count($exams))
                    <section>
                        <h2>Exams</h2>
                        <div class="et-grid et-grid--3">
                            @foreach($exams as $exam)
                                @include('frontend.components.exam-card', ['exam' => $exam])
                            @endforeach
                        </div>
                    </section>
                @endif

                @if(count($blogs))
                    <section>
                        <h2>Blogs</h2>
                        <div class="et-grid et-grid--3">
                            @foreach($blogs as $blog)
                                @include('frontend.components.blog-card', ['blog' => $blog])
                            @endforeach
                        </div>
                    </section>
                @endif

                @if(count($newsItems))
                    <section>
                        <h2>News</h2>
                        <div class="et-grid et-grid--3">
                            @foreach($newsItems as $item)
                                @include('frontend.components.news-card', ['news' => $item])
                            @endforeach
                        </div>
                    </section>
                @endif

                @if(count($categories))
                    <section>
                        <h2>Categories</h2>
                        <div class="et-grid et-grid--4">
                            @foreach($categories as $category)
                                @include('frontend.components.category-card', ['category' => $category])
                            @endforeach
                        </div>
                    </section>
                @endif
            @endunless
        @endif
    </div>
@endsection
