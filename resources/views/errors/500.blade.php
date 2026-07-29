@extends('frontend.layouts.app')

@section('content')
@include('errors.partials.content', [
    'code' => '500',
    'title' => 'Something went wrong',
    'message' => 'Our servers hit an unexpected bump. We are on it — please try again shortly.',
])
@endsection
