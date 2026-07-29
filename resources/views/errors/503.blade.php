@extends('frontend.layouts.app')

@section('content')
@include('errors.partials.content', [
    'code' => '503',
    'title' => 'Service unavailable',
    'message' => 'We are temporarily offline for maintenance. Please check back in a few minutes.',
])
@endsection
