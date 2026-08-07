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
    $modeLabel = filled($exam->exam_mode)
        ? ucfirst(str_replace('_', ' ', (string) $exam->exam_mode))
        : null;
@endphp

@section('content')
<div id="cx-main" class="cx-prepare-main" tabindex="-1">
<div class="cx-page-boot" id="cx-page-boot" role="status" aria-live="polite" aria-busy="true" aria-label="Loading exam readiness">
    <span class="cx-visually-hidden">Loading exam readiness</span>
    <div class="cx-page-boot__skeleton" aria-hidden="true">
        <div class="cx-skel cx-skel--eyebrow"></div>
        <div class="cx-skel cx-skel--title"></div>
        <div class="cx-skel cx-skel--line"></div>
        <div class="cx-skel-panel">
            <div class="cx-skel cx-skel--heading"></div>
            <div class="cx-skel cx-skel--line cx-skel--short"></div>
            <div class="cx-skel cx-skel--row"></div>
            <div class="cx-skel cx-skel--row"></div>
            <div class="cx-skel cx-skel--row"></div>
            <div class="cx-skel cx-skel--row"></div>
            <div class="cx-skel-row cx-skel-row--actions">
                <div class="cx-skel cx-skel--btn"></div>
                <div class="cx-skel cx-skel--btn"></div>
                <div class="cx-skel cx-skel--btn"></div>
            </div>
            <div class="cx-skel-media">
                <div class="cx-skel cx-skel--media"></div>
                <div class="cx-skel cx-skel--media"></div>
            </div>
        </div>
    </div>
</div>
<script src="{{ versioned_asset('js/candidate/page-boot.js') }}"></script>
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
     data-detect-devtools="{{ ($policy?->detect_devtools) ? '1' : '0' }}"
     data-rules-agreed="{{ !empty($rulesAgreed) ? '1' : '0' }}"
     data-agree-url="{{ $agreeUrl ?? route('frontend.exams.rules.agree', $exam) }}"
     data-warning-limit="{{ (int) ($policy?->focus_violation_limit ?? 3) }}">

    <div class="cx-prepare__hero">
        <div class="cx-prepare__hero-inner">
            <p class="cx-eyebrow">Exam readiness</p>
            @include('frontend.partials.ad-placement', ['page' => 'exam_prepare', 'position' => 'above_title'])
            <h1>{{ $exam->title }}</h1>
            <p>Complete the required checks below. Verification is driven only by enabled exam rules.</p>
            @include('frontend.partials.ad-placement', ['page' => 'exam_prepare', 'position' => 'below_title'])
        </div>
    </div>

<div class="cx-prepare__panel cx-prepare__layout">
        <main class="cx-prepare__content">
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
            @include('frontend.partials.ad-placement', ['page' => 'exam_prepare', 'position' => 'between_sections'])

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
            @include('frontend.partials.ad-placement', ['page' => 'exam_prepare', 'position' => 'after_details'])

            <div class="cx-prepare__media{{ ($requireWebcam || $requireSelfie) ? ' is-visible' : '' }}" id="cx-prepare-media" @if(!($requireWebcam || $requireSelfie)) hidden @endif>
                <figure class="cx-prepare__media-slot" data-slot="live">
                    <div class="cx-prepare__media-frame">
                        <video id="cx-preview" autoplay muted playsinline class="cx-preview" hidden></video>
                        <div class="cx-prepare__media-placeholder" id="cx-preview-placeholder">
                            <span>Live camera</span>
                            <small>Allow camera to show preview</small>
                        </div>
                    </div>
                    <figcaption>Live recording</figcaption>
                </figure>
                @if($requireSelfie)
                    <figure class="cx-prepare__media-slot" data-slot="selfie">
                        <div class="cx-prepare__media-frame">
                            <img id="cx-photo-preview" alt="Captured selfie" class="cx-photo" hidden>
                            <div class="cx-prepare__media-placeholder" id="cx-photo-placeholder">
                                <span>Selfie</span>
                                <small>Capture to preview here</small>
                            </div>
                        </div>
                        <figcaption>Captured selfie</figcaption>
                    </figure>
                @endif
                <canvas id="cx-snapshot-canvas" hidden></canvas>
            </div>
            <p id="cx-mic-level" class="cx-prepare__hint" hidden>Listening for microphone…</p>
            <p id="cx-ready-msg" class="cx-ready-msg" data-state="blocked" role="status" aria-live="polite">
                Start is disabled until required checks are complete.
            </p>
            @if($requireSelfie)
                <p class="cx-prepare__hint">Tip: Selfies must be captured live from your webcam. File uploads are not accepted.</p>
            @endif
        </section>
        @include('frontend.partials.ad-placement', ['page' => 'exam_prepare', 'position' => 'after_content'])
        </main>

        <aside class="cx-prepare__aside" aria-label="Exam readiness summary">
            <section class="cx-card cx-prepare-summary">
                <div class="cx-prepare-summary__head">
                    <span class="cx-prepare-summary__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-3zM9 12l2 2 4-4"/></svg>
                    </span>
                    <div>
                        <p>Secure attempt</p>
                        <h2>Ready to start?</h2>
                    </div>
                </div>
                <dl class="cx-prepare-summary__facts">
                    <div><dt>Duration</dt><dd>{{ (int) $exam->duration }} min</dd></div>
                    <div><dt>Questions</dt><dd>{{ (int) $exam->total_questions }}</dd></div>
                    <div><dt>Total marks</dt><dd>{{ (int) $exam->total_marks }}</dd></div>
                    @if($modeLabel)<div><dt>Exam mode</dt><dd>{{ $modeLabel }}</dd></div>@endif
                    <div><dt>Required checks</dt><dd>{{ count($checks) }}</dd></div>
                    <div><dt>Warning limit</dt><dd>{{ (int) ($policy?->focus_violation_limit ?? 3) }}</dd></div>
                </dl>
                <p class="cx-prepare-summary__note">Your answers save automatically after the exam begins. Keep this window active and your connection stable.</p>
            </section>
            @include('frontend.partials.ad-placement', ['page' => 'exam_prepare', 'position' => 'right_after_summary'])

            <section class="cx-card cx-prepare-consent" aria-label="Exam agreement">
                <label class="et-agree cx-prepare-agree">
                    <input type="checkbox" id="cx-prepare-rules-agree" @checked(!empty($rulesAgreed))>
                    <span>I agree to the exam rules and monitoring policies, including the warning limit of <strong>{{ (int) ($policy?->focus_violation_limit ?? 3) }}</strong>.</span>
                </label>
            </section>
            @include('frontend.partials.ad-placement', ['page' => 'exam_prepare', 'position' => 'right_after_consent'])

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
        @include('frontend.partials.ad-placement', ['page' => 'exam_prepare', 'position' => 'right_after_start'])
        <p id="cx-prepare-error" class="cx-error" hidden role="alert"></p>
        </aside>

</div>

    <div class="cx-loading" id="cx-loading" hidden>
        <div class="cx-loading__card">
            <div class="cx-spinner"></div>
            <h2>Preparing your exam</h2>
            <div class="cx-progress"><div class="cx-progress__bar" id="cx-progress-bar" style="width:0%"></div></div>
            <p id="cx-loading-step">Starting...</p>
            <p class="cx-save-state">Please wait — this usually takes a few seconds.</p>
            <button type="button" class="et-btn et-btn--ghost cx-loading__cancel" id="cx-cancel-start">Cancel</button>
        </div>
    </div>
</div>

<div id="cx-runner-host" class="cx-runner-host" hidden aria-hidden="true"></div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/components/rich-text-editor.css') }}">
@endpush

@push('scripts')
    <script src="{{ versioned_asset('js/components/editor.js') }}" defer></script>
    @vite(['resources/js/candidate/app.js'])
    <script src="{{ versioned_asset('js/candidate/prepare-boot.js') }}" defer></script>
@endpush
