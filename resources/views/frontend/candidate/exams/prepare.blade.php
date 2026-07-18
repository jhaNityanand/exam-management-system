@extends('frontend.candidate.layouts.exam')

@section('title', 'Prepare — '.$exam->title)

@php
    $policy = $policy ?? $exam->proctoringPolicy;
    $hardFullscreen = ($exam->exam_mode === 'proctored') && (bool) ($policy?->require_fullscreen);
    $requireWebcam = (bool) ($policy?->require_webcam);
    $requireMic = (bool) ($policy?->require_microphone);
    $requirePhoto = (bool) ($policy?->require_photo_verification);
    $canContinue = ! empty($evaluation['can_continue']) && ! empty($evaluation['active_attempt_id']);
@endphp

@section('content')
<div class="cx-prepare" id="cx-prepare"
     data-start-url="{{ route('frontend.exams.attempts.start', $exam) }}"
     data-require-webcam="{{ $requireWebcam ? '1' : '0' }}"
     data-require-mic="{{ $requireMic ? '1' : '0' }}"
     data-require-fullscreen="{{ $hardFullscreen ? '1' : '0' }}"
     data-require-photo="{{ $requirePhoto ? '1' : '0' }}"
     data-suggest-fullscreen="{{ ($policy?->require_fullscreen && ! $hardFullscreen) ? '1' : '0' }}">

    <div class="cx-prepare__hero">
        <div class="cx-prepare__hero-inner">
            <p class="cx-eyebrow">Exam preparation</p>
            <h1>{{ $exam->title }}</h1>
            <p>Check permissions, set your preferences, then start. Any signed-in user can attempt a public exam.</p>
            <div class="cx-chip-row">
                <span class="cx-chip">{{ (int) $exam->duration }} min</span>
                <span class="cx-chip">{{ (int) $exam->total_questions }} questions</span>
                <span class="cx-chip">{{ (int) $exam->total_marks }} marks</span>
                <span class="cx-chip">{{ strtoupper((string) ($exam->language ?: 'en')) }}</span>
            </div>
        </div>
    </div>

    <div class="cx-prepare__panel">
        <div id="cx-prepare-alert" class="cx-alert" hidden></div>

        @if(! empty($evaluation['reasons']))
            <div class="cx-alert cx-alert--danger" role="alert">
                {{ $evaluation['reasons'][0] }}
            </div>
        @elseif($canContinue)
            <div class="cx-alert cx-alert--info">
                You already have an active attempt. Starting will resume that session.
            </div>
        @endif

        <section class="cx-card">
            <div class="cx-card__head">
                <h2>Permissions</h2>
                <p>Grant only what this exam requires.</p>
            </div>
            <ul class="cx-perm-list" id="cx-perm-list">
                <li data-perm="webcam">
                    <div>
                        <strong>Webcam</strong>
                        <small>{{ $requireWebcam ? 'Required before start' : 'Not required for this exam' }}</small>
                    </div>
                    <span class="cx-status">{{ $requireWebcam ? 'Required' : 'Optional' }}</span>
                </li>
                <li data-perm="microphone">
                    <div>
                        <strong>Microphone</strong>
                        <small>{{ $requireMic ? 'Required before start' : 'Not required for this exam' }}</small>
                    </div>
                    <span class="cx-status">{{ $requireMic ? 'Required' : 'Optional' }}</span>
                </li>
                <li data-perm="fullscreen">
                    <div>
                        <strong>Fullscreen</strong>
                        <small>
                            @if($hardFullscreen)
                                Required for this proctored exam
                            @elseif($policy?->require_fullscreen)
                                Recommended for fewer distractions
                            @else
                                Optional
                            @endif
                        </small>
                    </div>
                    <span class="cx-status">{{ $hardFullscreen ? 'Required' : 'Optional' }}</span>
                </li>
                <li data-perm="clipboard">
                    <div>
                        <strong>Clipboard restrictions</strong>
                        <small>Applied automatically once the exam starts</small>
                    </div>
                    <span class="cx-status">Info</span>
                </li>
            </ul>
            <div class="cx-actions">
                @if($requireWebcam || $requireMic || $requirePhoto)
                    <button type="button" class="et-btn et-btn--ghost" id="cx-request-permissions">Allow camera / mic</button>
                @endif
                <button type="button" class="et-btn et-btn--ghost" id="cx-request-fullscreen">Enter fullscreen</button>
            </div>
            <video id="cx-preview" autoplay muted playsinline class="cx-preview" hidden></video>
            <canvas id="cx-snapshot-canvas" hidden></canvas>
        </section>

        <section class="cx-card">
            <div class="cx-card__head">
                <h2>Your preferences</h2>
                <p>These lock after the exam starts.</p>
            </div>
            <div class="cx-form-grid">
                <label>Theme
                    <select id="pref-theme">
                        <option value="light" selected>Light</option>
                        <option value="dark">Dark</option>
                        <option value="system">System</option>
                    </select>
                </label>
                <label>Font size
                    <select id="pref-font">
                        <option value="sm">Small</option>
                        <option value="md" selected>Medium</option>
                        <option value="lg">Large</option>
                    </select>
                </label>
                <label>Language
                    <select id="pref-lang">
                        <option value="en" @selected(($exam->language ?: 'en') === 'en')>English</option>
                        <option value="hi" @selected(($exam->language ?: 'en') === 'hi')>Hindi</option>
                    </select>
                </label>
                <label>Question palette
                    <select id="pref-palette">
                        <option value="right" selected>Right</option>
                        <option value="left">Left</option>
                    </select>
                </label>
            </div>
        </section>

        @if($requirePhoto)
        <section class="cx-card">
            <div class="cx-card__head">
                <h2>Photo verification</h2>
                <p>Capture a clear photo before starting.</p>
            </div>
            <button type="button" class="et-btn et-btn--ghost" id="cx-capture-photo">Capture photo</button>
            <img id="cx-photo-preview" alt="Captured photo" class="cx-photo" hidden>
        </section>
        @endif

        <div class="cx-prepare__footer">
            <a href="{{ route('frontend.exams.rules', $exam) }}" class="et-btn et-btn--ghost">Back to rules</a>
            <button type="button"
                    class="et-btn et-btn--primary"
                    id="cx-start-exam"
                    @disabled(! empty($evaluation['reasons']) && empty($evaluation['can_continue']))>
                {{ $canContinue ? 'Continue exam' : 'Start exam' }}
            </button>
        </div>
        <p id="cx-prepare-error" class="cx-error" hidden></p>
        <p class="cx-prepare__hint">Tip: If something fails, the error appears above. You can retry without reloading.</p>
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
@endsection

@push('scripts')
<script src="{{ versioned_asset('js/candidate/prepare-boot.js') }}" defer></script>
@endpush
