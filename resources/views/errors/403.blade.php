@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Access denied',
        'description' => 'You do not have permission to view this page.',
        'robots' => 'noindex, follow',
        'image_type' => 'organization',
    ];
@endphp

@section('content')
@include('errors.partials.content', [
    'code' => '403',
    'title' => 'Access denied',
    'message' => 'You do not have permission to view this page. If you think this is a mistake, try signing in with another account.',
])
@endsection
