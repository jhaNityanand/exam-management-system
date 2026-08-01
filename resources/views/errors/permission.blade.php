@extends('frontend.layouts.app')

@php
    $status = 403;
    $meta = \App\Support\FrontendErrorPages::meta($status, $message ?? null);
    $pageTitle = $title ?? $meta['title'];
    $pageMessage = $message ?? $meta['message'];
    $seo = [
        'title' => $pageTitle,
        'description' => \Illuminate\Support\Str::limit(strip_tags($pageMessage), 160),
        'robots' => 'noindex, follow',
        'image_type' => 'organization',
    ];
@endphp

@section('content')
@include('errors.partials.page', [
    'code' => '403',
    'title' => $pageTitle,
    'message' => $pageMessage,
    'showHome' => true,
    'showLogin' => $showLogin ?? ! auth()->check(),
    'showAccount' => $showAccount ?? auth()->check(),
])
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/frontend/errors.css') }}">
@endpush
