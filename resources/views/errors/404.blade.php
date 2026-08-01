@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Page not found',
        'description' => 'The page you are looking for does not exist or may have moved.',
        'robots' => 'noindex, follow',
        'image_type' => 'organization',
    ];
@endphp

@section('content')
@include('errors.partials.content', [
    'code' => '404',
    'title' => 'Page not found',
    'message' => 'We looked everywhere, but this page seems to have taken a break. Try searching or head back home.',
])
@endsection
