@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'Result — '.$exam->title, 'robots' => 'noindex, nofollow', 'image_type' => 'exam'];
    $needsFeedback = ! empty($needsFeedback);
@endphp

@push('styles')
<link rel="stylesheet" href="{{ versioned_asset('css/frontend/attempt-result.css') }}">
<link rel="stylesheet" href="{{ versioned_asset('css/frontend/feedback.css') }}">
@endpush

@section('content')
<x-ad-layout page="exam_result">
<div class="rs-page" id="rs-page"
     data-url="{{ $dataUrl }}"
     data-visible="{{ $visible ? '1' : '0' }}"
     data-exam-title="{{ $exam->title }}"
     data-exam-id="{{ $exam->id }}"
     data-attempt-id="{{ $attempt->id }}"
     data-needs-feedback="{{ $needsFeedback ? '1' : '0' }}"
     data-feedback-store-url="{{ $feedbackStoreUrl ?? route('frontend.feedback.store') }}"
     data-feedback-skip-url="{{ $feedbackSkipUrl ?? route('frontend.feedback.skip') }}">
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

        <x-ad-slot page="exam_result" position="below_title" />

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

            <x-ad-slot page="exam_result" position="after_stats" />
            <x-ad-slot page="exam_result" position="after_content" />
        @endif
    </div>

    @if($needsFeedback)
        <div class="fb-modal" id="fb-result-modal" role="dialog" aria-modal="true" aria-labelledby="fb-result-title" hidden>
            <div class="fb-modal__backdrop" data-fb-skip></div>
            <div class="fb-modal__card">
                <h2 id="fb-result-title">How was your exam?</h2>
                <p class="fb-modal__lead">Optional feedback helps improve future sessions. You can skip anytime.</p>
                <x-feedback-form
                    :exam="$exam"
                    :attempt-id="$attempt->id"
                    :store-url="$feedbackStoreUrl ?? route('frontend.feedback.store')"
                    source="result_modal"
                >
                    <x-slot:actions>
                        <button type="button" class="et-btn et-btn--ghost" data-fb-skip>Skip</button>
                    </x-slot:actions>
                </x-feedback-form>
            </div>
        </div>
    @endif
</div>
</x-ad-layout>
@endsection

@push('scripts')
<script src="{{ versioned_asset('js/frontend/feedback.js') }}" defer></script>
@if($visible)
<script src="{{ versioned_asset('js/frontend/attempt-result.js') }}" defer></script>
@endif
@endpush
