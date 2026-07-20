@extends('frontend.candidate.layouts.exam')

@section('title', 'Prepare — '.$exam->title)

@php
    $policy = $policy ?? $exam->proctoringPolicy;
    $requireWebcam = (bool) ($policy?->require_webcam);
    $requireMic = (bool) ($policy?->require_microphone);
    $requireFullscreen = (bool) ($policy?->require_fullscreen);
    $requireSelfie = (bool) ($policy?->require_photo_verification || $policy?->require_identity_verification);
    $canContinue = ! empty($evaluation['can_continue']) && ! empty($evaluation['active_attempt_id']);
    $checks = $checks ?? [];
    $blockedByEligibility = ! empty($evaluation['reasons']) && empty($evaluation['can_continue']);
@endphp

@section('content')
<div class="cx-prepare" id="cx-prepare"
     data-start-url="{{ route('frontend.exams.attempts.start', $exam) }}"
     data-verify-url="{{ route('frontend.exams.verification', $exam) }}"
     data-started-url="{{ route('frontend.exams.started', $exam) }}"
     data-challenge-token="{{ $challenge->token }}"
     data-require-webcam="{{ $requireWebcam ? '1' : '0' }}"
     data-require-mic="{{ $requireMic ? '1' : '0' }}"
     data-require-fullscreen="{{ $requireFullscreen ? '1' : '0' }}"
     data-require-selfie="{{ $requireSelfie ? '1' : '0' }}"
     data-block-context="{{ ($policy?->block_context_menu) ? '1' : '0' }}"
     data-detect-devtools="{{ ($policy?->detect_devtools) ? '1' : '0' }}">

    <div class="cx-prepare__hero">
        <div class="cx-prepare__hero-inner">
            <p class="cx-eyebrow">Exam readiness</p>
            <h1>{{ $exam->title }}</h1>
            <p>Complete the required checks below. Verification is driven only by enabled exam rules.</p>
            <div class="cx-chip-row">
                <span class="cx-chip">{{ (int) $exam->duration }} min</span>
                <span class="cx-chip">{{ (int) $exam->total_questions }} questions</span>
                <span class="cx-chip">{{ (int) $exam->total_marks }} marks</span>
                <span class="cx-chip">{{ count($checks) }} checks</span>
            </div>
        </div>
    </div>

    <div class="cx-prepare__panel">
        <div id="cx-prepare-alert" class="cx-alert" hidden></div>

        @if($blockedByEligibility)
            <div class="cx-alert cx-alert--danger" role="alert">
                {{ $evaluation['reasons'][0] }}
            </div>
        @elseif($canContinue)
            <div class="cx-alert cx-alert--info">
                You already have an active attempt. Starting will resume that session.
            </div>
        @endif

        <section class="cx-card cx-card--ready">
            <div class="cx-card__head cx-card__head--row">
                <div>
                    <h2>Verification checklist</h2>
                    <p>Only requirements enabled by this exam’s rules are shown.</p>
                </div>
                <button type="button" class="cx-help-btn" id="cx-help-toggle" aria-expanded="false" aria-controls="cx-help-panel" title="How to complete verification">
                    <span class="cx-help-btn__icon" aria-hidden="true">i</span>
                    <span class="cx-visually-hidden">Instructions</span>
                </button>
            </div>

            <div class="cx-help-panel" id="cx-help-panel" hidden>
                <h3>How to complete checks</h3>
                <ol>
                    <li>Click <strong>Allow camera / mic</strong>. When the browser prompt appears, choose <strong>Allow</strong>.</li>
                    <li>If no prompt appears, open the lock/camera icon in the address bar and set Camera/Microphone to Allow, then retry.</li>
                    <li>If a device is missing, connect a webcam/microphone and retry. Close apps that may already be using them.</li>
                    @if($requireFullscreen)
                        <li>Click <strong>Enter fullscreen</strong> and stay in fullscreen until the exam ends.</li>
                    @endif
                    @if($requireSelfie)
                        <li>When the live preview is visible, click <strong>Capture selfie</strong>. Uploads are not accepted.</li>
                    @endif
                    <li><strong>Start exam</strong> stays disabled until every required check shows Granted/Captured.</li>
                </ol>
            </div>

            @if(empty($checks))
                <p class="cx-prepare__hint">No special device checks are required for this exam. You can start when ready.</p>
            @else
                <ul class="cx-perm-list" id="cx-perm-list">
                    @foreach($checks as $check)
                        <li data-perm="{{ $check['key'] }}">
                            <div>
                                <strong>{{ $check['label'] }}</strong>
                                <small>{{ $check['description'] }}</small>
                            </div>
                            <span class="cx-status" data-state="{{ !empty($check['informational']) ? 'info' : 'required' }}">
                                {{ !empty($check['informational']) ? 'Info' : 'Required' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="cx-actions">
                @if($requireWebcam || $requireMic || $requireSelfie)
                    <button type="button" class="et-btn et-btn--ghost" id="cx-request-permissions">Allow camera / mic</button>
                @endif
                @if($requireFullscreen)
                    <button type="button" class="et-btn et-btn--ghost" id="cx-request-fullscreen">Enter fullscreen</button>
                @endif
                @if($requireSelfie)
                    <button type="button" class="et-btn et-btn--ghost" id="cx-capture-photo" disabled>Capture selfie</button>
                    <button type="button" class="et-btn et-btn--ghost" id="cx-retake-photo" hidden>Retake selfie</button>
                @endif
            </div>

            <div class="cx-prepare__media">
                <video id="cx-preview" autoplay muted playsinline class="cx-preview" hidden></video>
                <img id="cx-photo-preview" alt="Captured selfie" class="cx-photo" hidden>
                <canvas id="cx-snapshot-canvas" hidden></canvas>
            </div>
            <p id="cx-mic-level" class="cx-prepare__hint" hidden>Listening for microphone…</p>
            <p id="cx-ready-msg" class="cx-ready-msg" data-state="blocked" role="status" aria-live="polite">
                Start is disabled until required checks are complete.
            </p>
        </section>

        <div class="cx-prepare__footer">
            <a href="{{ route('frontend.exams.rules', $exam) }}" class="et-btn et-btn--ghost">Back to rules</a>
            <button type="button"
                    class="et-btn et-btn--primary"
                    id="cx-start-exam"
                    disabled
                    aria-disabled="true"
                    @if($blockedByEligibility) data-force-disabled="1" @endif>
                {{ $canContinue ? 'Continue exam' : 'Start exam' }}
            </button>
        </div>
        <p id="cx-prepare-error" class="cx-error" hidden role="alert"></p>
        <p class="cx-prepare__hint">Tip: Selfies must be captured live from your webcam. File uploads are not accepted.</p>
    </div>

    <div class="cx-loading" id="cx-loading" hidden>
        <div class="cx-loading__card">
            <div class="cx-spinner"></div>
            <h2>Preparing your exam</h2>
            <div class="cx-progress"><div class="cx-progress__bar" id="cx-progress-bar" style="width:0%"></div></div>
            <p id="cx-loading-step">Starting...</p>
            <p class="cx-save-state">Please wait — this usually takes a few seconds.</p>
            <button type="button" class="et-btn et-btn--ghost" id="cx-cancel-start" style="margin-top:1rem">Cancel</button>
        </div>
    </div>
</div>

<div id="cx-runner-host" class="cx-runner-host" hidden aria-hidden="true"></div>
@endsection

@push('scripts')
    @vite(['resources/js/candidate/app.js'])
    <script src="{{ versioned_asset('js/candidate/prepare-boot.js') }}" defer></script>
@endpush
