@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Page expired',
        'description' => 'Your session timed out. Refresh the page and try again.',
        'robots' => 'noindex, follow',
        'image_type' => 'organization',
    ];
@endphp

@section('content')
@include('errors.partials.content', [
    'code' => '419',
    'title' => 'Page expired',
    'message' => 'Your session timed out for security. Refresh the page and try again.',
])
@endsection
