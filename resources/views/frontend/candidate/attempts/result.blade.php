@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'Result — '.$exam->title];
@endphp

@push('styles')
<link rel="stylesheet" href="{{ versioned_asset('css/frontend/attempt-result.css') }}">
@endpush

@section('content')
<div class="rs-page" id="rs-page"
     data-url="{{ $dataUrl }}"
     data-visible="{{ $visible ? '1' : '0' }}"
     data-exam-title="{{ $exam->title }}">
    <div class="et-container rs-shell">
        <header class="rs-hero">
            <div class="rs-hero__copy">
                <p class="rs-eyebrow">Attempt result</p>
                <h1>Exam result</h1>
                <p class="rs-hero__sub">{{ $exam->title }}</p>
            </div>
            <div class="rs-hero__actions">
                <a href="{{ route('frontend.exams.show', $exam) }}" class="et-btn et-btn--ghost">Exam page</a>
                <a href="{{ route('frontend.account.results') }}" class="et-btn et-btn--ghost">All results</a>
            </div>
        </header>

        <div id="rs-error" class="rs-error" hidden role="alert"></div>

        @if(! $visible)
            <section class="rs-locked" id="rs-locked">
                <div class="rs-locked__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M7 10V8a5 5 0 0 1 10 0v2M6 10h12v10H6V10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                </div>
                <h2>Results are not available yet</h2>
                <p>Your attempt has been submitted. The institution will release results according to the exam policy.</p>
                <div class="rs-actions">
                    <a href="{{ route('frontend.account.results') }}" class="et-btn et-btn--primary">Back to my results</a>
                    <a href="{{ route('frontend.exams.show', $exam) }}" class="et-btn et-btn--ghost">Exam page</a>
                </div>
            </section>
        @else
            <section id="rs-skeleton" class="rs-panel rs-panel--skeleton" aria-hidden="true">
                <div class="rs-skel rs-skel--banner"></div>
                <div class="rs-stats">
                    @for($i = 0; $i < 8; $i++)
                        <div class="rs-skel rs-skel--stat"></div>
                    @endfor
                </div>
                <div class="rs-skel rs-skel--bar"></div>
                <div class="rs-skel rs-skel--actions"></div>
            </section>

            <section id="rs-content" class="rs-content" hidden></section>
        @endif
    </div>
</div>
@endsection

@push('scripts')
@if($visible)
<script src="{{ versioned_asset('js/frontend/attempt-result.js') }}" defer></script>
@endif
@endpush
