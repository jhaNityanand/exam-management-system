@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'Result — '.$exam->title];
@endphp

@section('content')
<div class="et-page-hero">
    <div class="et-container">
        <h1>Exam result</h1>
        <p>{{ $exam->title }}</p>
    </div>
</div>

<div class="et-container" style="padding:1.5rem 0 3rem;display:grid;gap:1.25rem">
    @if(! $visible)
        <div class="et-card" style="padding:1.25rem">
            <h2 style="margin-top:0">Results are not available yet</h2>
            <p>Your attempt has been submitted. The institution will release results according to the exam policy.</p>
            <a href="{{ route('frontend.account.results') }}" class="et-btn et-btn--primary">Back to my results</a>
        </div>
    @else
        <div class="et-grid et-grid--4">
            <div class="et-stat"><span class="et-stat__value">{{ number_format((float) $attempt->score, 2) }}</span><span class="et-stat__label">Score</span></div>
            <div class="et-stat"><span class="et-stat__value">{{ number_format((float) $attempt->percentage, 1) }}%</span><span class="et-stat__label">Percentage</span></div>
            <div class="et-stat"><span class="et-stat__value">{{ $attempt->passed ? 'Pass' : 'Fail' }}</span><span class="et-stat__label">Status</span></div>
            <div class="et-stat"><span class="et-stat__value">{{ gmdate('H:i:s', (int) ($attempt->time_spent_seconds ?? 0)) }}</span><span class="et-stat__label">Time spent</span></div>
        </div>

        <div class="et-card" style="padding:1.25rem">
            <h2 style="margin-top:0">Summary</h2>
            <ul>
                <li>Correct answers: {{ (int) $attempt->correct_count }}</li>
                <li>Wrong answers: {{ (int) $attempt->wrong_count }}</li>
                <li>Unanswered: {{ (int) $attempt->unanswered_count }}</li>
                <li>Submission: {{ ucfirst(str_replace('_', ' ', (string) $attempt->submission_reason ?: $attempt->status)) }}</li>
            </ul>
            <div style="display:flex;gap:.65rem;flex-wrap:wrap;margin-top:1rem">
                <a href="{{ route('frontend.attempts.review', $attempt) }}" class="et-btn et-btn--primary">Question review</a>
                <a href="{{ route('frontend.exams.show', $exam) }}" class="et-btn et-btn--ghost">Back to exam</a>
                <a href="{{ route('frontend.account.results') }}" class="et-btn et-btn--ghost">All results</a>
            </div>
        </div>
    @endif
</div>
@endsection
