@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'Rules — '.$exam->title, 'robots' => 'noindex, nofollow', 'image_type' => 'exam'];
    $policy = $policy ?? $exam->proctoringPolicy;
    $warningLimit = (int) ($policy?->focus_violation_limit ?? 3);
    $rulesAgreed = ! empty($rulesAgreed);
    $agreeUrl = $agreeUrl ?? route('frontend.exams.rules.agree', $exam);
    $canContinueAttempt = ! empty($evaluation['can_continue']) && ! empty($evaluation['active_attempt_id']);
    $needsPayment = ! empty($evaluation['requires_payment']);
    $canAttempt = (! empty($evaluation['can_attempt']) || empty($evaluation['reasons'])) && ! $needsPayment && ! $canContinueAttempt;
    $formats = collect($exam->exam_format ?? [])->map(fn ($f) => str_replace('_', ' ', ucfirst((string) $f)))->implode(', ');
@endphp

@section('content')
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

<div class="et-container et-page-stack">
    <div class="et-grid et-grid--4">
        <div class="et-stat"><span class="et-stat__value">{{ (int) $exam->total_questions }}</span><span class="et-stat__label">Questions</span></div>
        <div class="et-stat"><span class="et-stat__value">{{ (int) $exam->duration }}</span><span class="et-stat__label">Minutes</span></div>
        <div class="et-stat"><span class="et-stat__value">{{ (int) $exam->total_marks }}</span><span class="et-stat__label">Total marks</span></div>
        <div class="et-stat"><span class="et-stat__value">{{ (int) $exam->passing_marks }}</span><span class="et-stat__label">Passing marks</span></div>
    </div>

    <div class="et-callout et-callout--warning et-warning-limit" role="note">
        <strong>Warnings allowed: {{ $warningLimit }}</strong>
        @if($warningLimit === 0)
            <p>No warnings are allowed. The first monitored violation (such as switching tabs) can auto-submit your exam.</p>
        @else
            <p>You may receive up to <strong>{{ $warningLimit }}</strong> monitored warning{{ $warningLimit === 1 ? '' : 's' }} (tab switch, focus loss, etc.). Reaching this limit auto-submits your exam.</p>
        @endif
    </div>

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
        </ul>
    </div>

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

    @include('frontend.partials.exam-rules', ['rules' => $rules])

    <div class="et-card et-rules-actions" id="cx-rules-actions"
         data-agree-url="{{ $agreeUrl }}"
         data-rules-agreed="{{ $rulesAgreed ? '1' : '0' }}">
        @if($canAttempt)
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
                        data-url="{{ route('frontend.exams.purchase', $exam) }}"
                        data-reload="1">Purchase Exam</button>
                <span class="et-text-muted">Payment is required before continuing.</span>
            @elseif($canContinueAttempt)
                <a href="{{ route('frontend.exams.started', $exam) }}" class="et-btn et-btn--primary">Continue Exam</a>
            @elseif($canAttempt)
                <a href="{{ route('frontend.exams.prepare', $exam) }}"
                   class="et-btn et-btn--primary"
                   id="cx-rules-continue"
                   @unless($rulesAgreed) aria-disabled="true" tabindex="-1" @endunless>
                    Continue to verification
                </a>
            @else
                <span class="et-text-muted">{{ $evaluation['reasons'][0] ?? 'You cannot start this exam right now.' }}</span>
            @endif
            <a href="{{ route('frontend.exams.show', $exam) }}" class="et-btn et-btn--ghost">Back to exam details</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ versioned_asset('js/frontend/exam-purchase.js') }}" defer></script>
<script src="{{ versioned_asset('js/frontend/exam-rules.js') }}" defer></script>
@endpush
