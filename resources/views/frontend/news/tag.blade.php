@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'News tagged '.$tag->name, 'description' => 'News tagged with '.$tag->name];
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'News', 'url' => route('frontend.news.index')],
                ['label' => '#'.$tag->name],
            ]])
            <h1>#{{ $tag->name }}</h1>
            <p>News stories tagged with {{ $tag->name }}.</p>
        </div>
    </div>

    <div class="et-container et-section">
        @if(($news ?? collect())->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No news for this tag', 'message' => ''])
        @else
            <div class="et-grid et-grid--3" data-load-more-list>
                @foreach($news as $item)
                    @include('frontend.components.news-card', ['news' => $item])
                @endforeach
            </div>
            @include('frontend.partials.load-more', [
                'paginator' => $news,
                'endpoint' => route('frontend.news.tag', array_merge([$tag->slug], request()->query())),
            ])
        @endif
    </div>
@endsection
