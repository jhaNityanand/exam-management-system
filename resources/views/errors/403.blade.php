@extends('frontend.layouts.app')

@section('content')
@include('errors.partials.content', [
    'code' => '403',
    'title' => 'Access denied',
    'message' => 'You do not have permission to view this page. If you think this is a mistake, try signing in with another account.',
])
@endsection
