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
    $modeLabel = filled($exam->exam_mode)
        ? ucfirst(str_replace('_', ' ', (string) $exam->exam_mode))
        : null;
    $amountLabel = $isPaidExam
        ? trim(($exam->exam_currency ?: 'INR').' '.number_format((float) ($exam->exam_amount ?? 0), 2))
        : null;
    $blockedReason = (! $needsPayment && ! $canContinueAttempt && ! $canAttempt)
        ? ($evaluation['reasons'][0] ?? 'You cannot start this exam right now.')
        : null;
@endphp

@section('content')
<div class="et-page-hero et-exam-rules-hero">
    <div class="et-container">
        @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
            ['label' => 'Exams', 'url' => route('frontend.exams.index')],
            ['label' => $exam->title, 'url' => route('frontend.exams.show', $exam)],
            ['label' => 'Rules'],
        ]])
        <span class="et-exam-rules-hero__eyebrow">Before you begin</span>
        @include('frontend.partials.ad-placement', ['page' => 'exam_rules', 'position' => 'above_title'])
        <h1>{{ $exam->title }}</h1>
        <p>Review the exam rules, monitoring requirements, and assessment details before verification.</p>
        @include('frontend.partials.ad-placement', ['page' => 'exam_rules', 'position' => 'below_title'])
    </div>
</div>

<div class="et-container et-page-stack et-exam-rules-page">
    @if($blockedReason)
        <div class="et-exam-rules-blocked" role="alert">
            <span class="et-exam-rules-blocked__icon" aria-hidden="true">!</span>
            <strong>{{ $blockedReason }}</strong>
        </div>
    @endif

    <div class="et-grid et-grid--4 et-exam-rules-stats">
        <div class="et-stat"><span class="et-stat__value">{{ (int) $exam->total_questions }}</span><span class="et-stat__label">Questions</span></div>
        <div class="et-stat"><span class="et-stat__value">{{ (int) $exam->duration }}</span><span class="et-stat__label">Minutes</span></div>
        <div class="et-stat"><span class="et-stat__value">{{ (int) $exam->total_marks }}</span><span class="et-stat__label">Total marks</span></div>
        <div class="et-stat"><span class="et-stat__value">{{ (int) $exam->passing_marks }}</span><span class="et-stat__label">Passing marks</span></div>
    </div>
    @include('frontend.partials.ad-placement', ['page' => 'exam_rules', 'position' => 'after_stats'])

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

    <div class="et-exam-rules-layout">
    <main class="et-exam-rules-main">
    <div class="et-callout et-callout--warning et-warning-limit et-exam-rules-warning" role="note">
        <strong>Warnings allowed: {{ $warningLimit }}</strong>
        @if($warningLimit === 0)
            <p>No warnings are allowed. The first monitored violation (such as switching tabs) can auto-submit your exam.</p>
        @else
            <p>You may receive up to <strong>{{ $warningLimit }}</strong> monitored warning{{ $warningLimit === 1 ? '' : 's' }} (tab switch, focus loss, etc.). Reaching this limit auto-submits your exam.</p>
        @endif
    </div>
    @include('frontend.partials.ad-placement', ['page' => 'exam_rules', 'position' => 'after_about'])

<div class="et-card et-card--padded et-exam-rules-card">
        <h2 class="et-heading-flush">Assessment summary</h2>
        <ul class="et-detail-list">
            <li><strong>Question types:</strong> {{ $formats ?: '—' }}</li>
            @if($modeLabel)
                <li><strong>Exam mode:</strong> {{ $modeLabel }}</li>
            @endif
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
    @include('frontend.partials.ad-placement', ['page' => 'exam_rules', 'position' => 'after_details'])

<div class="et-card et-card--padded et-exam-rules-card">
        <h2 class="et-heading-flush">Instructions for candidates</h2>
        <div class="et-prose">
            @if($exam->instructions)
                <x-rich-text-content :content="$exam->instructions" />
            @else
                <p>No custom instructions provided.</p>
            @endif
        </div>
    </div>
    @include('frontend.partials.ad-placement', ['page' => 'exam_rules', 'position' => 'between_sections'])

@include('frontend.partials.exam-rules', ['rules' => $rules])
    @include('frontend.partials.ad-placement', ['page' => 'exam_rules', 'position' => 'after_content'])

    </main>

    <aside class="et-exam-rules-aside" aria-label="Start exam">
    <div class="et-rules-actions et-exam-rules-stack" id="cx-rules-actions"
         data-agree-url="{{ $agreeUrl }}"
         data-rules-agreed="{{ $rulesAgreed ? '1' : '0' }}">
        <section class="et-card et-exam-rules-gate et-exam-rules-summary">
            <div class="et-exam-rules-gate__head">
                <span class="et-exam-rules-gate__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <div>
                    <p>Your next step</p>
                    <h2>{{ $needsPayment ? 'Unlock this exam' : ($canContinueAttempt ? 'Resume your attempt' : 'Ready for verification?') }}</h2>
                </div>
            </div>

            <dl class="et-exam-rules-gate__facts">
                <div><dt>Duration</dt><dd>{{ (int) $exam->duration }} min</dd></div>
                <div><dt>Questions</dt><dd>{{ (int) $exam->total_questions }}</dd></div>
                @if($modeLabel)<div><dt>Mode</dt><dd>{{ $modeLabel }}</dd></div>@endif
                <div><dt>Warnings</dt><dd>{{ $warningLimit }}</dd></div>
            </dl>
        </section>
        @include('frontend.partials.ad-placement', ['page' => 'exam_rules', 'position' => 'right_after_next_step'])

        @if($showAgree)
            <section class="et-card et-exam-rules-consent">
                <label class="et-agree">
                    <input type="checkbox" id="cx-rules-agree" @checked($rulesAgreed)>
                    <span>I have read and agree to the exam rules, instructions, and monitoring policies above.</span>
                </label>
            </section>
            @include('frontend.partials.ad-placement', ['page' => 'exam_rules', 'position' => 'right_after_consent'])
        @endif

        @if($needsPayment)
            <section class="et-exam-rules-action">
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
            </section>
        @elseif($canContinueAttempt)
            <section class="et-exam-rules-action">
                <a href="{{ route('frontend.exams.started', $exam) }}" class="et-btn et-btn--primary">Continue Exam</a>
            </section>
        @elseif($canAttempt)
            <section class="et-exam-rules-action">
                <a href="{{ route('frontend.exams.prepare', $exam) }}"
                   class="et-btn et-btn--primary"
                   id="cx-rules-continue"
                   data-cx-rules-gate
                   @unless($rulesAgreed) aria-disabled="true" tabindex="-1" @endunless>
                    Continue to verification
                </a>
            </section>
        @endif
        @include('frontend.partials.ad-placement', ['page' => 'exam_rules', 'position' => 'right_after_start'])

        <section class="et-exam-rules-action et-exam-rules-action--secondary">
            <a href="{{ route('frontend.exams.show', $exam) }}" class="et-btn et-btn--ghost">Back to exam details</a>
        </section>
        @include('frontend.partials.ad-placement', ['page' => 'exam_rules', 'position' => 'right_after_back'])
    </div>
    </aside>
    </div>

</div>
@endsection

@push('scripts')
<script src="{{ versioned_asset('js/frontend/exam-purchase.js') }}" defer></script>
<script src="{{ versioned_asset('js/frontend/exam-rules.js') }}" defer></script>
@endpush
