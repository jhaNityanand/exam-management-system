@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'Rules — '.$exam->title, 'robots' => 'noindex, nofollow', 'image_type' => 'exam'];
    $policy = $policy ?? $exam->proctoringPolicy;
    $warningLimit = (int) ($policy?->focus_violation_limit ?? 3);
    $rulesAgreed = ! empty($rulesAgreed);
    $agreeUrl = $agreeUrl ?? route('frontend.exams.rules.agree', $exam);
    $canContinueAttempt = ! empty($evaluation['can_continue']) && ! empty($evaluation['active_attempt_id']);
    $needsPayment = ! empty($evaluation['requires_payment']);
    $isPaidExam = $exam->isPaid();
    $hasPaid = ! empty($evaluation['has_entitlement']) && $isPaidExam;
    $canAttempt = (! empty($evaluation['can_attempt']) || empty($evaluation['reasons'])) && ! $needsPayment && ! $canContinueAttempt;
    $showAgree = $needsPayment || $canAttempt;
    $formats = collect($exam->exam_format ?? [])->map(fn ($f) => str_replace('_', ' ', ucfirst((string) $f)))->implode(', ');
    $amountLabel = $isPaidExam
        ? trim(($exam->exam_currency ?: 'INR').' '.number_format((float) ($exam->exam_amount ?? 0), 2))
        : null;
@endphp

@section('content')
<x-ad-layout page="exam_rules">
<div class="et-page-hero">
    <div class="et-container">
        @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
            ['label' => 'Exams', 'url' => route('frontend.exams.index')],
            ['label' => $exam->title, 'url' => route('frontend.exams.show', $exam)],
            ['label' => 'Rules'],
        ]])
        <h1>Exam rules & verification</h1>
        <p>Review instructions and requirements before you begin.</p>
    </div>
</div>

<x-ad-slot page="exam_rules" position="below_title" />

<div class="et-container et-page-stack">
    <div class="et-grid et-grid--4">
        <div class="et-stat"><span class="et-stat__value">{{ (int) $exam->total_questions }}</span><span class="et-stat__label">Questions</span></div>
        <div class="et-stat"><span class="et-stat__value">{{ (int) $exam->duration }}</span><span class="et-stat__label">Minutes</span></div>
        <div class="et-stat"><span class="et-stat__value">{{ (int) $exam->total_marks }}</span><span class="et-stat__label">Total marks</span></div>
        <div class="et-stat"><span class="et-stat__value">{{ (int) $exam->passing_marks }}</span><span class="et-stat__label">Passing marks</span></div>
    </div>

    <x-ad-slot page="exam_rules" position="after_stats" />

    @if($isPaidExam && ! $canContinueAttempt)
        <ol class="et-rules-steps" aria-label="Before you start">
            <li class="et-rules-steps__item {{ $needsPayment ? 'is-current' : 'is-done' }}">
                <span class="et-rules-steps__num">1</span>
                <span class="et-rules-steps__label">Payment</span>
            </li>
            <li class="et-rules-steps__item {{ ! $needsPayment ? 'is-current' : '' }}">
                <span class="et-rules-steps__num">2</span>
                <span class="et-rules-steps__label">Verification</span>
            </li>
        </ol>
    @endif

    <div class="et-callout et-callout--warning et-warning-limit" role="note">
        <strong>Warnings allowed: {{ $warningLimit }}</strong>
        @if($warningLimit === 0)
            <p>No warnings are allowed. The first monitored violation (such as switching tabs) can auto-submit your exam.</p>
        @else
            <p>You may receive up to <strong>{{ $warningLimit }}</strong> monitored warning{{ $warningLimit === 1 ? '' : 's' }} (tab switch, focus loss, etc.). Reaching this limit auto-submits your exam.</p>
        @endif
    </div>

    <x-ad-slot page="exam_rules" position="after_about" />

    <div class="et-card et-card--padded">
        <h2 class="et-heading-flush">Assessment summary</h2>
        <ul class="et-detail-list">
            <li><strong>Question types:</strong> {{ $formats ?: '—' }}</li>
            <li><strong>Negative marking:</strong>
                @if($exam->enable_negative_marking)
                    Enabled ({{ $exam->negative_marking_type ?: 'custom' }})
                @else
                    Disabled
                @endif
            </li>
            <li><strong>Timezone:</strong> {{ $exam->timezone ?: config('app.timezone') }}</li>
            <li><strong>Current time:</strong> <span id="cx-current-time">{{ now()->timezone($exam->timezone ?: config('app.timezone'))->format('d M Y H:i:s T') }}</span></li>
            <li><strong>Browser requirements:</strong> Latest Chrome, Edge, Firefox, or Safari with JavaScript enabled.
                @if($policy?->require_webcam) Webcam required.@endif
                @if($policy?->require_microphone) Microphone required.@endif
                @if($policy?->require_fullscreen) Fullscreen required.@endif
            </li>
            <li><strong>Warnings allowed:</strong> {{ $warningLimit }}</li>
            @if($isPaidExam)
                <li><strong>Pricing:</strong> {{ $amountLabel ?: 'Paid' }}
                    @if($hasPaid)
                        <span class="et-badge et-badge--success">Paid</span>
                    @elseif($needsPayment)
                        <span class="et-badge et-badge--slate">Payment required</span>
                    @endif
                </li>
            @endif
        </ul>
    </div>

    <x-ad-slot page="exam_rules" position="after_details" />

    <div class="et-card et-card--padded">
        <h2 class="et-heading-flush">Instructions for candidates</h2>
        <div class="et-prose">
            @if($exam->instructions)
                <x-rich-text-content :content="$exam->instructions" />
            @else
                <p>No custom instructions provided.</p>
            @endif
        </div>
    </div>

    <x-ad-slot page="exam_rules" position="between_sections" />

    @include('frontend.partials.exam-rules', ['rules' => $rules])

    <x-ad-slot page="exam_rules" position="after_content" />

    <div class="et-card et-rules-actions" id="cx-rules-actions"
         data-agree-url="{{ $agreeUrl }}"
         data-rules-agreed="{{ $rulesAgreed ? '1' : '0' }}">
        @if($showAgree)
            <label class="et-agree">
                <input type="checkbox" id="cx-rules-agree" @checked($rulesAgreed)>
                <span>I have read and agree to the exam rules, instructions, and monitoring policies above.</span>
            </label>
        @endif

        <div class="et-rules-actions__bar">
            @if($needsPayment)
                <button type="button"
                        class="et-btn et-btn--primary"
                        id="rules-purchase-btn"
                        data-exam-purchase
                        data-cx-rules-gate
                        data-url="{{ route('frontend.exams.purchase', $exam) }}"
                        data-reload="1"
                        @unless($rulesAgreed) disabled aria-disabled="true" @endunless>
                    Purchase Exam{{ $amountLabel ? ' — '.$amountLabel : '' }}
                </button>
                <span class="et-text-muted">Complete payment first. Verification unlocks after payment.</span>
            @elseif($canContinueAttempt)
                <a href="{{ route('frontend.exams.started', $exam) }}" class="et-btn et-btn--primary">Continue Exam</a>
            @elseif($canAttempt)
                <a href="{{ route('frontend.exams.prepare', $exam) }}"
                   class="et-btn et-btn--primary"
                   id="cx-rules-continue"
                   data-cx-rules-gate
                   @unless($rulesAgreed) aria-disabled="true" tabindex="-1" @endunless>
                    Continue to verification
                </a>
            @else
                <span class="et-text-muted">{{ $evaluation['reasons'][0] ?? 'You cannot start this exam right now.' }}</span>
            @endif
            <a href="{{ route('frontend.exams.show', $exam) }}" class="et-btn et-btn--ghost">Back to exam details</a>
        </div>
    </div>

    <x-ad-slot page="exam_rules" position="after_cta" />
</div>
</x-ad-layout>
@endsection

@push('styles')
<style>
.et-rules-steps {
    display: flex;
    gap: 0.75rem;
    list-style: none;
    margin: 0;
    padding: 0;
    flex-wrap: wrap;
}
.et-rules-steps__item {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.45rem 0.85rem;
    border-radius: 999px;
    border: 1px solid var(--et-border, #334155);
    color: var(--et-muted, #94a3b8);
    font-size: 0.85rem;
    font-weight: 600;
}
.et-rules-steps__num {
    display: inline-grid;
    place-items: center;
    width: 1.4rem;
    height: 1.4rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--et-border, #334155) 70%, transparent);
    font-size: 0.75rem;
}
.et-rules-steps__item.is-current {
    border-color: var(--et-accent, #0f766e);
    color: var(--et-text, #e2e8f0);
    background: color-mix(in srgb, var(--et-accent, #0f766e) 14%, transparent);
}
.et-rules-steps__item.is-current .et-rules-steps__num,
.et-rules-steps__item.is-done .et-rules-steps__num {
    background: var(--et-accent, #0f766e);
    color: #fff;
}
.et-rules-steps__item.is-done {
    border-color: color-mix(in srgb, var(--et-accent, #0f766e) 45%, var(--et-border, #334155));
    color: var(--et-text, #e2e8f0);
}
.et-badge--success {
    background: color-mix(in srgb, #059669 18%, transparent);
    color: #34d399;
}
</style>
@endpush

@push('scripts')
<script src="{{ versioned_asset('js/frontend/exam-purchase.js') }}" defer></script>
<script src="{{ versioned_asset('js/frontend/exam-rules.js') }}" defer></script>
@endpush
