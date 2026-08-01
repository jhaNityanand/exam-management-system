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

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/components/rich-text-editor.css') }}">
@endpush

@push('scripts')
    <script src="{{ versioned_asset('js/components/editor.js') }}" defer></script>
    @vite(['resources/js/candidate/app.js'])
@endpush
