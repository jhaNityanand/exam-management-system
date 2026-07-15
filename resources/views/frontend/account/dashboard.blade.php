@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'Account dashboard'];
    $user = $user ?? auth()->user();
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            <h1>Hello, {{ $user->name ?? 'Learner' }}</h1>
            <p>Track your practice journey from one place.</p>
        </div>
    </div>

    <div class="et-container et-layout-2">
        @include('frontend.layouts.sidebar')
        <div>
            <div class="et-grid et-grid--3" style="margin-bottom:1.25rem">
                <div class="et-stat">
                    <span class="et-stat__value">{{ (int) ($stats['attempts'] ?? 0) }}</span>
                    <span class="et-stat__label">Attempts</span>
                </div>
                <div class="et-stat">
                    <span class="et-stat__value">{{ (int) ($stats['completed'] ?? 0) }}</span>
                    <span class="et-stat__label">Completed</span>
                </div>
                <div class="et-stat">
                    <span class="et-stat__value">{{ (int) ($stats['avg_score'] ?? 0) }}%</span>
                    <span class="et-stat__label">Avg score</span>
                </div>
            </div>

            <div class="et-card" style="padding:1.25rem">
                <h2 style="margin-top:0">Quick links</h2>
                <div style="display:flex;flex-wrap:wrap;gap:.65rem">
                    <a href="{{ route('frontend.exams.index') }}" class="et-btn et-btn--primary et-btn--sm">Browse exams</a>
                    <a href="{{ route('frontend.account.exams') }}" class="et-btn et-btn--ghost et-btn--sm">My exams</a>
                    <a href="{{ route('frontend.account.results') }}" class="et-btn et-btn--ghost et-btn--sm">Results</a>
                </div>
            </div>
        </div>
    </div>
@endsection
