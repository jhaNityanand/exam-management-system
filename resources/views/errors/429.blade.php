@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Too many requests',
        'description' => 'Rate limit exceeded. Please wait a moment and try again.',
        'robots' => 'noindex, follow',
        'image_type' => 'organization',
    ];
@endphp

@section('content')
@include('errors.partials.content', [
    'code' => '429',
    'title' => 'Too many requests',
    'message' => 'You are moving a little too fast. Please wait a moment and try again.',
])
@endsection
