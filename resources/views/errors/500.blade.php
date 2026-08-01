@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Something went wrong',
        'description' => 'An unexpected server error occurred. Please try again shortly.',
        'robots' => 'noindex, follow',
        'image_type' => 'organization',
    ];
@endphp

@section('content')
@include('errors.partials.content', [
    'code' => '500',
    'title' => 'Something went wrong',
    'message' => 'Our servers hit an unexpected bump. We are on it — please try again shortly.',
])
@endsection
