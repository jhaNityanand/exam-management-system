@extends('frontend.account.layout')

@php
    $seo = ['title' => 'Activity tracking'];
@endphp

@section('account-eyebrow', 'Insights')
@section('account-title', 'Activity Tracking')
@section('account-lead', 'A polished timeline for login history, exam attempts, and security events — coming soon.')

@section('account-content')
    <section class="ca-card ca-coming">
        <div class="ca-coming__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><path d="M12 8v5l3 2M12 22a10 10 0 1 0-10-10 10 10 0 0 0 10 10Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </div>
        <span class="ca-badge is-info">Coming soon</span>
        <h2>Activity tracking is on the way</h2>
        <p>Soon you’ll see a full timeline of account activity with filters, device details, and security alerts — designed for exam platforms.</p>

        <div class="ca-timeline">
            <article class="ca-timeline__item">
                <div class="ca-timeline__dot" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" stroke="currentColor" stroke-width="1.8"/></svg>
                </div>
                <div>
                    <strong>Login history</strong>
                    <p class="ca-help">See when and where you signed in.</p>
                </div>
            </article>
            <article class="ca-timeline__item">
                <div class="ca-timeline__dot" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M7 4h10v16l-5-2-5 2V4Z" stroke="currentColor" stroke-width="1.8"/></svg>
                </div>
                <div>
                    <strong>Exam attempts</strong>
                    <p class="ca-help">Track starts, submissions, and score outcomes.</p>
                </div>
            </article>
            <article class="ca-timeline__item">
                <div class="ca-timeline__dot" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 19h16M8 15V9m4 6V5m4 10v-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </div>
                <div>
                    <strong>Profile updates</strong>
                    <p class="ca-help">Audit changes to your personal details.</p>
                </div>
            </article>
            <article class="ca-timeline__item">
                <div class="ca-timeline__dot" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="3" width="14" height="18" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M9 8h6M9 12h6M9 16h3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </div>
                <div>
                    <strong>Device activity</strong>
                    <p class="ca-help">Browsers and devices used with your account.</p>
                </div>
            </article>
            <article class="ca-timeline__item">
                <div class="ca-timeline__dot" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 3 4 7v5c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V7l-8-4Z" stroke="currentColor" stroke-width="1.8"/></svg>
                </div>
                <div>
                    <strong>Security events</strong>
                    <p class="ca-help">Password changes and sensitive account actions.</p>
                </div>
            </article>
        </div>
    </section>
@endsection
