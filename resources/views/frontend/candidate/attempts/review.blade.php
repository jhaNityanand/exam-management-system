@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'Review — '.$exam->title];
@endphp

@push('styles')
<link rel="stylesheet" href="{{ versioned_asset('css/frontend/attempt-review.css') }}">
@endpush

@section('content')
<div class="rv-page" id="rv-page"
     data-url="{{ $dataUrl }}"
     data-exam-title="{{ $exam->title }}">
    <div class="et-container rv-shell">
        <header class="rv-hero">
            <div class="rv-hero__copy">
                <p class="rv-eyebrow">Attempt review</p>
                <h1>Question review</h1>
                <p class="rv-hero__sub">{{ $exam->title }}</p>
            </div>
            <div class="rv-hero__actions">
                <a href="{{ route('frontend.attempts.result', $attempt) }}" class="et-btn et-btn--ghost">Back to result</a>
                <a href="{{ route('frontend.exams.show', $exam) }}" class="et-btn et-btn--ghost">Exam page</a>
            </div>
        </header>

        <div id="rv-error" class="rv-error" hidden role="alert"></div>

        <section id="rv-summary-skeleton" class="rv-summary rv-summary--skeleton" aria-hidden="true">
            <div class="rv-skel rv-skel--banner"></div>
            <div class="rv-summary__grid">
                @for($i = 0; $i < 8; $i++)
                    <div class="rv-skel rv-skel--stat"></div>
                @endfor
            </div>
            <div class="rv-skel rv-skel--bar"></div>
        </section>

        <section id="rv-summary" class="rv-summary" hidden></section>

        <section class="rv-list-head">
            <h2>Questions</h2>
            <p id="rv-list-meta" class="rv-list-meta">Loading review…</p>
        </section>

        <div id="rv-questions-skeleton" class="rv-list" aria-hidden="true">
            @for($i = 0; $i < 3; $i++)
                <article class="rv-card rv-card--skeleton">
                    <div class="rv-skel rv-skel--line rv-skel--w40"></div>
                    <div class="rv-skel rv-skel--line rv-skel--w90"></div>
                    <div class="rv-skel rv-skel--line rv-skel--w70"></div>
                    <div class="rv-skel rv-skel--block"></div>
                </article>
            @endfor
        </div>

        <div id="rv-questions" class="rv-list" hidden></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ versioned_asset('js/frontend/attempt-review.js') }}" defer></script>
@endpush
