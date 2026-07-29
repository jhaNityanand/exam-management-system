@extends('frontend.layouts.app')

@section('content')
@include('errors.partials.content', [
    'code' => '404',
    'title' => 'Page not found',
    'message' => 'We looked everywhere, but this page seems to have taken a break. Try searching or head back home.',
])
@endsection
