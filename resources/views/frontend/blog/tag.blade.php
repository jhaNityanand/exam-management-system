@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => '#'.($tag->name ?? 'tag'),
        'description' => 'Blog posts tagged '.($tag->name ?? '').'.',
    ];
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Blogs', 'url' => route('frontend.blogs.index')],
                ['label' => '#'.($tag->name ?? 'tag')],
            ]])
            <h1>#{{ $tag->name ?? 'tag' }}</h1>
            @if(!empty($tag->description))
                <p>{{ $tag->description }}</p>
            @endif
        </div>
    </div>

    <div class="et-container et-section">
        @if(($blogs ?? collect())->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No posts with this tag', 'message' => ''])
        @else
            <div class="et-grid et-grid--3" data-load-more-list>
                @foreach($blogs as $blog)
                    @include('frontend.components.blog-card', ['blog' => $blog])
                @endforeach
            </div>
            @include('frontend.partials.load-more', [
                'paginator' => $blogs,
                'endpoint' => route('frontend.blogs.tag', $tag->slug).(($qs = request()->getQueryString()) ? '?'.$qs : ''),
            ])
        @endif
    </div>
@endsection
