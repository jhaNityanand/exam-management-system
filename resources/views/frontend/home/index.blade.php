@extends('frontend.layouts.app')

@section('content')
    @foreach(($page['sections'] ?? collect()) as $key => $section)
        @includeIf('frontend.home.partials.'.$key, [
            'section' => $section,
            'page' => $page,
        ])
    @endforeach
@endsection
