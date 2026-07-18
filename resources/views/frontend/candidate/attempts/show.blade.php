@extends('frontend.candidate.layouts.exam')

@section('title', $exam->title)

@php
    $urls = [
        'answers' => route('frontend.attempts.answers', $attempt),
        'heartbeat' => route('frontend.attempts.heartbeat', $attempt),
        'events' => route('frontend.attempts.events', $attempt),
        'submit' => route('frontend.attempts.submit', $attempt),
        'result' => route('frontend.attempts.result', $attempt),
    ];
@endphp

@section('content')
<div class="cx-mobile-bar">
    <strong>{{ \Illuminate\Support\Str::limit($exam->title, 28) }}</strong>
    <div id="cx-mobile-timer" class="cx-timer">--:--</div>
</div>

<div class="cx-exam"
     id="cx-exam"
     data-user-id="{{ auth()->id() }}"
     data-payload='@json($payload)'
     data-urls='@json($urls)'>

    <aside class="cx-side cx-side--info">
        <h2 class="cx-side__name">{{ auth()->user()->name }}</h2>
        <p class="cx-side__title">{{ $exam->title }}</p>
        <p id="cx-progress-label" class="cx-meta">0 / 0 answered</p>
        <p id="cx-qno" class="cx-meta">Question 1</p>
        <p id="cx-save-state" class="cx-save-state" data-state="saved">Saved</p>
        <p id="cx-nav-hint" class="cx-nav-hint" hidden></p>
    </aside>

    <main class="cx-main">
        <div id="cx-question"></div>
        <label class="cx-review-toggle">
            <input type="checkbox" id="cx-mark-review"> Mark for review
        </label>
        <div class="cx-footer-actions">
            <button type="button" class="cx-btn cx-btn--ghost cx-btn--icon" id="cx-prev"
                    aria-label="Previous question" title="Previous question">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <button type="button" class="cx-btn cx-btn--ghost cx-btn--icon" id="cx-next"
                    aria-label="Next question" title="Next question">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
            </button>
            <button type="button" class="cx-btn cx-btn--ghost cx-btn--sm" id="cx-skip">Skip</button>
            <button type="button" class="cx-btn cx-btn--warn cx-btn--sm" id="cx-clear">Clear selected</button>
            <button type="button" class="cx-btn cx-btn--primary cx-btn--sm" id="cx-submit">Save &amp; next</button>
        </div>
    </main>

    <aside class="cx-side cx-side--palette">
        <div id="cx-timer" class="cx-timer">--:--</div>
        <h3 class="cx-palette-title">Question palette</h3>
        <div class="cx-palette" id="cx-palette"></div>
        <ul class="cx-legend">
            <li>Green = Answered</li>
            <li>Yellow = Marked for review</li>
            <li>Grey = Visited</li>
            <li>Outline = Current</li>
        </ul>
        <button type="button" class="cx-btn cx-btn--danger cx-btn--sm cx-final-submit" id="cx-final-submit">
            Final submit
        </button>
    </aside>
</div>

<div class="cx-modal" id="cx-submit-modal" hidden aria-hidden="true">
    <div class="cx-modal__backdrop" data-close-modal></div>
    <div class="cx-modal__card" role="dialog" aria-modal="true" aria-labelledby="cx-submit-title">
        <h2 id="cx-submit-title">Review before final submit</h2>
        <p class="cx-modal__lead">Please confirm your attempt summary.</p>
        <ul class="cx-modal__stats" id="cx-submit-stats"></ul>
        <div class="cx-modal__actions">
            <button type="button" class="cx-btn cx-btn--ghost cx-btn--sm" data-close-modal>Continue review</button>
            <button type="button" class="cx-btn cx-btn--primary cx-btn--sm" id="cx-confirm-submit">Submit exam</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/candidate/app.js'])
@endpush
