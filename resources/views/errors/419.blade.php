@extends('frontend.layouts.app')

@section('content')
@include('errors.partials.content', [
    'code' => '419',
    'title' => 'Page expired',
    'message' => 'Your session timed out for security. Refresh the page and try again.',
])
@endsection
