@extends('frontend.candidate.layouts.exam')

@section('title', $exam->title)

@section('content')
    @include('frontend.candidate.attempts.partials.runner', [
        'attempt' => $attempt,
        'exam' => $exam,
        'payload' => $payload,
        'asOverlay' => false,
    ])
@endsection

@push('scripts')
    @vite(['resources/js/candidate/app.js'])
@endpush
