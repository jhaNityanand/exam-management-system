@extends('frontend.layouts.app')

@section('content')
    @foreach(($page['sections'] ?? collect()) as $key => $section)
        @includeIf('frontend.home.partials.'.$key, [
            'section' => $section,
            'page' => $page,
        ])
        @if($key === 'hero')
            <div class="et-container">{!! ad_slot('home_sidebar') !!}</div>
        @endif
    @endforeach
@endsection
