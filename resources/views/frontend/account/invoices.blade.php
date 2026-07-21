@extends('frontend.account.layout')

@php
    $seo = ['title' => 'Invoices'];
@endphp

@section('account-eyebrow', 'Billing')
@section('account-title', 'Invoices')
@section('account-lead', 'Purchase history, receipts, and payment records — coming soon.')

@section('account-content')
    <section class="ca-card ca-coming">
        <div class="ca-coming__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><path d="M7 3h10a2 2 0 0 1 2 2v16l-3-1.5L13 21l-3-1.5L7 21V5a2 2 0 0 1 2-2Zm3 5h4M10 12h4M10 16h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <span class="ca-badge is-info">Coming soon</span>
        <h2>Invoices are on the way</h2>
        <p>Soon you’ll download receipts, track paid exam purchases, and manage billing history in one place.</p>

        <div class="ca-timeline">
            <article class="ca-timeline__item">
                <div class="ca-timeline__dot" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </div>
                <div>
                    <strong>Invoice list</strong>
                    <p class="ca-help">Browse paid exams and subscription charges.</p>
                </div>
            </article>
            <article class="ca-timeline__item">
                <div class="ca-timeline__dot" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div>
                    <strong>PDF downloads</strong>
                    <p class="ca-help">Save tax-ready receipts anytime.</p>
                </div>
            </article>
            <article class="ca-timeline__item">
                <div class="ca-timeline__dot" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="6" width="18" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M3 10h18" stroke="currentColor" stroke-width="1.8"/></svg>
                </div>
                <div>
                    <strong>Payment methods</strong>
                    <p class="ca-help">See cards and gateways used for purchases.</p>
                </div>
            </article>
            <article class="ca-timeline__item">
                <div class="ca-timeline__dot" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 3 4 7v5c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V7l-8-4Z" stroke="currentColor" stroke-width="1.8"/></svg>
                </div>
                <div>
                    <strong>Refund status</strong>
                    <p class="ca-help">Track refunds and disputed payments.</p>
                </div>
            </article>
        </div>
    </section>
@endsection
