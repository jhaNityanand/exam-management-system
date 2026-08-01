@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Service unavailable',
        'description' => 'We are temporarily offline for maintenance.',
        'robots' => 'noindex, follow',
        'image_type' => 'organization',
    ];
@endphp

@section('content')
@include('errors.partials.content', [
    'code' => '503',
    'title' => 'Service unavailable',
    'message' => 'We are temporarily offline for maintenance. Please check back in a few minutes.',
])
@endsection
