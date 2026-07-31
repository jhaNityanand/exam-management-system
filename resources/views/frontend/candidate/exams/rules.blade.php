@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'Rules — '.$exam->title];
    $policy = $policy ?? $exam->proctoringPolicy;
    $warningLimit = (int) ($policy?->focus_violation_limit ?? 3);
    $rulesAgreed = ! empty($rulesAgreed);
    $agreeUrl = $agreeUrl ?? route('frontend.exams.rules.agree', $exam);
    $canContinueAttempt = ! empty($evaluation['can_continue']) && ! empty($evaluation['active_attempt_id']);
    $needsPayment = ! empty($evaluation['requires_payment']);
    $canAttempt = (! empty($evaluation['can_attempt']) || empty($evaluation['reasons'])) && ! $needsPayment && ! $canContinueAttempt;
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

<div class="et-container" style="padding:1.5rem 0 3rem;display:grid;gap:1.25rem">
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

    <div class="et-card" style="padding:1.25rem">
        <h2 style="margin-top:0">Assessment summary</h2>
        <ul>
            <li><strong>Question types:</strong> {{ collect($exam->exam_format ?? [])->map(fn($f)=>str_replace('_',' ',ucfirst($f)))->implode(', ') }}</li>
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

    <div class="et-card" style="padding:1.25rem">
        <h2 style="margin-top:0">Instructions for candidates</h2>
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
        @unless($canContinueAttempt || $needsPayment)
            <label class="et-agree">
                <input type="checkbox" id="cx-rules-agree" @checked($rulesAgreed)>
                <span>I have read and agree to the exam rules, instructions, and monitoring policies above.</span>
            </label>
        @endunless

        @if($needsPayment)
            <button type="button" class="et-btn et-btn--primary" id="rules-purchase-btn"
                    data-url="{{ route('frontend.exams.purchase', $exam) }}">Purchase Exam</button>
            <span style="color:var(--et-text-muted)">Payment is required before continuing.</span>
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
            <span style="color:var(--et-text-muted)">{{ $evaluation['reasons'][0] ?? 'You cannot start this exam right now.' }}</span>
        @endif
        <a href="{{ route('frontend.exams.show', $exam) }}" class="et-btn et-btn--ghost">Back to exam details</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
setInterval(() => {
    const el = document.getElementById('cx-current-time');
    if (el) el.textContent = new Date().toLocaleString();
}, 1000);

document.getElementById('rules-purchase-btn')?.addEventListener('click', async (e) => {
    let confirmed = false;
    if (window.Swal && typeof window.Swal.fire === 'function') {
        const result = await window.Swal.fire({
            title: 'Complete payment?',
            text: 'Complete placeholder payment for this exam?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Continue',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
        });
        confirmed = !!(result && result.isConfirmed);
    } else {
        confirmed = confirm('Complete placeholder payment?');
    }
    if (!confirmed) return;

    const res = await fetch(e.currentTarget.dataset.url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    });
    if (res.ok) {
        location.reload();
        return;
    }
    if (window.EmsToast?.error) {
        window.EmsToast.error('Payment failed');
    } else if (window.Swal?.fire) {
        window.Swal.fire({ icon: 'error', title: 'Payment failed' });
    } else {
        alert('Payment failed');
    }
});

(function () {
    const wrap = document.getElementById('cx-rules-actions');
    const checkbox = document.getElementById('cx-rules-agree');
    const continueBtn = document.getElementById('cx-rules-continue');
    if (!wrap || !checkbox || !continueBtn) return;

    const agreeUrl = wrap.getAttribute('data-agree-url') || '';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function setContinueEnabled(enabled) {
        if (enabled) {
            continueBtn.removeAttribute('aria-disabled');
            continueBtn.removeAttribute('tabindex');
            continueBtn.classList.remove('is-disabled');
        } else {
            continueBtn.setAttribute('aria-disabled', 'true');
            continueBtn.setAttribute('tabindex', '-1');
            continueBtn.classList.add('is-disabled');
        }
    }

    setContinueEnabled(checkbox.checked);

    continueBtn.addEventListener('click', (e) => {
        if (!checkbox.checked) {
            e.preventDefault();
            checkbox.focus();
        }
    });

    checkbox.addEventListener('change', async () => {
        const agreed = checkbox.checked;
        setContinueEnabled(agreed);
        if (!agreeUrl) return;
        try {
            await fetch(agreeUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ agreed }),
            });
        } catch (err) {}
    });
})();
</script>
@endpush
