@extends('frontend.layouts.app')

@php
    $seo = ['title' => 'Rules — '.$exam->title];
    $policy = $policy ?? $exam->proctoringPolicy;
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
    @if(session('success'))
        <div class="et-alert et-alert--success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="et-alert et-alert--danger">{{ session('error') }}</div>
    @endif

    <div class="et-grid et-grid--4">
        <div class="et-stat"><span class="et-stat__value">{{ (int) $exam->total_questions }}</span><span class="et-stat__label">Questions</span></div>
        <div class="et-stat"><span class="et-stat__value">{{ (int) $exam->duration }}</span><span class="et-stat__label">Minutes</span></div>
        <div class="et-stat"><span class="et-stat__value">{{ (int) $exam->total_marks }}</span><span class="et-stat__label">Total marks</span></div>
        <div class="et-stat"><span class="et-stat__value">{{ (int) $exam->passing_marks }}</span><span class="et-stat__label">Passing marks</span></div>
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
        @if($rules->isNotEmpty())
            <h3>Rules & regulations</h3>
            <ol>
                @foreach($rules as $rule)
                    <li><strong>{{ $rule->title }}</strong>
                        @if($rule->description)
                            <div>{{ $rule->description }}</div>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </div>

    <div class="et-card" style="padding:1.25rem;display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
        @if(! empty($evaluation['requires_payment']))
            <button type="button" class="et-btn et-btn--primary" id="rules-purchase-btn"
                    data-url="{{ route('frontend.exams.purchase', $exam) }}">Purchase Exam</button>
            <span style="color:var(--et-text-muted)">Payment is required before continuing.</span>
        @elseif(! empty($evaluation['can_continue']) && ! empty($evaluation['active_attempt_id']))
            <a href="{{ route('frontend.attempts.show', $evaluation['active_attempt_id']) }}" class="et-btn et-btn--primary">Continue Exam</a>
        @elseif(! empty($evaluation['can_attempt']) || empty($evaluation['reasons']))
            <a href="{{ route('frontend.exams.prepare', $exam) }}" class="et-btn et-btn--primary">Continue to verification</a>
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
    if (!confirm('Complete placeholder payment?')) return;
    const res = await fetch(e.currentTarget.dataset.url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    });
    if (res.ok) location.reload();
    else alert('Payment failed');
});
</script>
@endpush
