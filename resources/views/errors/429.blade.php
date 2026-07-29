@extends('frontend.layouts.app')

@section('content')
@include('errors.partials.content', [
    'code' => '429',
    'title' => 'Too many requests',
    'message' => 'You are moving a little too fast. Please wait a moment and try again.',
])
@endsection
