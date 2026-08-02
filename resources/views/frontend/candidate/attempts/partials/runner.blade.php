@php
    $urls = $urls ?? [
        'answers' => route('frontend.attempts.answers', $attempt),
        'heartbeat' => route('frontend.attempts.heartbeat', $attempt),
        'events' => route('frontend.attempts.events', $attempt),
        'submit' => route('frontend.attempts.submit', $attempt),
        'result' => route('frontend.attempts.result', $attempt),
    ];
    $policy = $payload['policy'] ?? [];
    $requireWebcam = ! empty($policy['require_webcam']);
    $asOverlay = ! empty($asOverlay);
    $timedExam = ! empty($payload['exam']['enable_exam_timer']);
@endphp

<div class="cx-exam{{ $asOverlay ? ' cx-exam--overlay is-active' : '' }}"
     id="cx-exam"
     data-user-id="{{ auth()->id() }}"
     data-require-webcam="{{ $requireWebcam ? '1' : '0' }}"
     data-payload='@json($payload)'
     data-urls='@json($urls)'>

    <header class="cx-topbar" id="cx-topbar">
        <div class="cx-topbar__mobile-leading">
            <div id="cx-timer" class="cx-timer cx-timer--top" aria-live="polite" title="{{ $timedExam ? 'Time remaining' : 'Elapsed time' }}">--:--</div>
        </div>

        <div class="cx-topbar__left">
            <div class="cx-topbar__title-row">
                <span class="cx-live-badge">Live exam</span>
                <h1 class="cx-topbar__title" title="{{ $exam->title }}">{{ \Illuminate\Support\Str::limit($exam->title, 48) }}</h1>
            </div>
            <p class="cx-topbar__meta">
                <span id="cx-qno">Question 1</span>
                <span class="cx-dot" aria-hidden="true"></span>
                <span id="cx-progress-label">0 / 0 answered</span>
            </p>
        </div>

        <div class="cx-topbar__right">
            <p id="cx-save-state" class="cx-save-state" data-state="saved" title="Answers sync automatically">Saved</p>
            <button type="button"
                    class="cx-icon-btn cx-drawer-toggle"
                    id="cx-drawer-toggle"
                    aria-expanded="false"
                    aria-controls="cx-rail"
                    title="Open exam panel">
                <span class="cx-icon-btn__bars" aria-hidden="true"></span>
                <span class="cx-visually-hidden">Open exam panel</span>
            </button>
        </div>
    </header>

    @php
        $leftAdHtml = ad_slot('exam_attempt', 'left_sidebar');
        $bottomAdHtml = ad_slot('exam_attempt', 'below_content');
        $rightAdHtml = ad_slot('exam_attempt', 'right_sidebar');
    @endphp
    <div class="cx-exam__body {{ $leftAdHtml !== '' ? 'cx-exam__body--with-left' : '' }}">
        @if($leftAdHtml !== '')
            <aside class="cx-ad cx-ad--left" data-exam-ad="left" aria-label="Sponsored">{!! $leftAdHtml !!}</aside>
        @endif
        <main class="cx-main" id="cx-main">
            <x-ad-slot page="exam_attempt" position="above_title" />
            <div class="cx-question-head">
                <div>
                    <p class="cx-question-kicker" id="cx-question-kicker">Question 1</p>
                    <p class="cx-question-marks" id="cx-question-marks"></p>
                </div>
            </div>

            <div id="cx-question" class="cx-question-wrap"></div>

            @if($bottomAdHtml !== '')
                <div class="cx-ad" data-exam-ad="bottom">{!! $bottomAdHtml !!}</div>
            @endif

            <div class="cx-footer-actions">
                <button type="button" class="cx-btn cx-btn--ghost" id="cx-clear-selection">Clear selection</button>
                <button type="button" class="cx-btn cx-btn--ghost" id="cx-skip">Skip</button>
                <button type="button" class="cx-btn cx-btn--primary" id="cx-submit">Save &amp; next</button>
                <button type="button" class="cx-btn cx-btn--review" id="cx-mark-review-next">Mark for review &amp; next</button>
            </div>
        </main>

        <div class="cx-drawer-backdrop" id="cx-drawer-backdrop" hidden></div>

        <aside class="cx-rail{{ $requireWebcam ? ' cx-rail--with-webcam' : '' }}" id="cx-rail" aria-label="Exam panel">
            @if($rightAdHtml !== '')
                <div class="cx-ad" data-exam-ad="right">{!! $rightAdHtml !!}</div>
            @endif
            <div class="cx-rail__head">
                <h2>Exam panel</h2>
                <button type="button" class="cx-icon-btn cx-drawer-close" id="cx-drawer-close" aria-label="Close panel">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="cx-rail__timer-wrap">
                <p class="cx-rail__label" id="cx-rail-timer-label">{{ $timedExam ? 'Time remaining' : 'Time elapsed' }}</p>
                <div id="cx-rail-timer" class="cx-timer cx-timer--rail" aria-live="off">--:--</div>
            </div>

            @if($requireWebcam)
                <section class="cx-webcam" id="cx-webcam" aria-label="Webcam monitor">
                    <p class="cx-rail__label">Webcam</p>
                    <div class="cx-webcam__frame">
                        <video id="cx-webcam-preview" autoplay muted playsinline></video>
                        <p id="cx-webcam-status" class="cx-webcam__status">Starting camera…</p>
                    </div>
                </section>
            @endif

            <section class="cx-rail__palette" aria-label="Question palette">
                <div class="cx-rail__palette-head">
                    <h3>Questions</h3>
                    <p id="cx-palette-summary" class="cx-rail__summary">0 answered</p>
                </div>
                <div id="cx-palette-parts" class="cx-palette-parts" hidden></div>
                <div class="cx-palette" id="cx-palette" role="list"></div>
                <ul class="cx-legend" aria-label="Question status legend">
                    <li><span class="cx-legend__swatch is-answered" aria-hidden="true"></span> Answered</li>
                    <li><span class="cx-legend__swatch is-pending" aria-hidden="true"></span> Save pending</li>
                    <li><span class="cx-legend__swatch is-review" aria-hidden="true"></span> Review</li>
                    <li><span class="cx-legend__swatch is-visited" aria-hidden="true"></span> Visited</li>
                    <li><span class="cx-legend__swatch is-current" aria-hidden="true"></span> Current</li>
                </ul>
            </section>

            <button type="button" class="cx-btn cx-btn--danger cx-final-submit" id="cx-final-submit">
                Final submit
            </button>
        </aside>
    </div>

    <div id="cx-toast" class="cx-toast" hidden role="status" aria-live="polite"></div>

    <div class="cx-modal" id="cx-violation-modal" hidden aria-hidden="true">
        <div class="cx-modal__backdrop" data-close-violation></div>
        <div class="cx-modal__card" role="dialog" aria-modal="true" aria-labelledby="cx-violation-title">
            <h2 id="cx-violation-title">Rule warning</h2>
            <p class="cx-modal__lead" id="cx-violation-message">A monitoring event was recorded.</p>
            <p class="cx-modal__advice" id="cx-violation-advice" hidden></p>
            <p class="cx-modal__meta" id="cx-violation-meta"></p>
            <div class="cx-modal__actions">
                <button type="button" class="cx-btn cx-btn--ghost" id="cx-violation-reconnect" hidden>Reconnect camera</button>
                <button type="button" class="cx-btn cx-btn--primary" id="cx-violation-ack" data-close-violation>I understand</button>
            </div>
        </div>
    </div>

    <div class="cx-modal" id="cx-submit-modal" hidden aria-hidden="true">
        <div class="cx-modal__backdrop" data-close-modal></div>
        <div class="cx-modal__card" role="dialog" aria-modal="true" aria-labelledby="cx-submit-title">
            <h2 id="cx-submit-title">Review before final submit</h2>
            <p class="cx-modal__lead">Please confirm your attempt summary.</p>
            <ul class="cx-modal__stats" id="cx-submit-stats"></ul>
            <div class="cx-modal__actions">
                <button type="button" class="cx-btn cx-btn--ghost" data-close-modal>Continue review</button>
                <button type="button" class="cx-btn cx-btn--danger" id="cx-confirm-submit">Submit exam</button>
            </div>
        </div>
    </div>
</div>
