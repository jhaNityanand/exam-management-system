@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => $exam->meta_title ?: $exam->title,
        'description' => $exam->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $exam->description), 160),
        'keywords' => $exam->meta_keywords,
        'canonical' => $exam->canonical_url ?: url()->current(),
        'og_title' => $exam->og_title,
        'og_description' => $exam->og_description,
        'image' => $exam->seoImageUrl(),
        'image_type' => 'exam',
        'breadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Exams', 'url' => route('frontend.exams.index')],
            ['label' => $exam->title],
        ],
    ];
    $isFree = ! $exam->isPaid();
    $attemptsLabel = ($exam->attempt_limit_type === 'unlimited' || (int) ($exam->max_attempts ?? 0) === 0)
        ? 'Unlimited'
        : (($exam->attempt_limit_type === 'once') ? '1' : (string) (int) $exam->max_attempts);
    $formats = collect($exam->exam_format ?? [])->map(fn ($f) => str_replace('_', ' ', ucfirst((string) $f)))->implode(', ');
    $returnUrl = route('frontend.exams.rules', $exam);
    $publishedAt = $exam->created_at;
    $publishedLabel = $publishedAt
        ? $publishedAt->timezone($exam->timezone ?: config('app.timezone'))->format('d M Y')
        : null;
    $hasDescription = filled(trim(strip_tags((string) ($exam->description ?? ''))));
    $policy = $exam->proctoringPolicy;
    $warningLimit = (int) ($policy?->focus_violation_limit ?? 3);
    $modeLabel = ucfirst(str_replace('_', ' ', (string) $exam->exam_mode));
    $priceLabel = $isFree
        ? 'Free to attempt'
        : strtoupper((string) ($exam->exam_currency ?: 'INR')).' '.number_format((float) $exam->exam_amount, 2);
    $scheduleLabel = ($exam->schedule_type ?? 'any_time') === 'fixed_window'
        ? (optional($exam->scheduled_start)->format('d M Y, H:i') ?: 'Date pending')
        : 'Available any time';

    $examTags = collect($exam->tags ?? [])
        ->map(fn ($tag) => trim((string) $tag))
        ->filter()
        ->unique()
        ->values();

    $categoryTrail = collect();
    $categoryCursor = $exam->category;
    $categoryGuard = 0;
    while ($categoryCursor && $categoryGuard < 12) {
        $categoryTrail->prepend($categoryCursor);
        $categoryCursor = $categoryCursor->parent;
        $categoryGuard++;
    }

    $seoCrumbs = [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Exams', 'url' => route('frontend.exams.index')],
    ];
    foreach ($categoryTrail as $trailCategory) {
        $seoCrumbs[] = [
            'label' => $trailCategory->name,
            'url' => route('frontend.categories.show', $trailCategory),
        ];
    }
    $seoCrumbs[] = ['label' => $exam->title];
    $seo['breadcrumbs'] = $seoCrumbs;
@endphp

@section('content')
<article class="et-exam-show">
        <header class="et-exam-show__hero">
            <div class="et-container et-exam-show__shell">
                @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                    ['label' => 'Home', 'url' => route('home')],
                    ['label' => 'Exams', 'url' => route('frontend.exams.index')],
                    ['label' => $exam->title],
                ]])

                <div class="et-exam-show__hero-copy">
                    <h1>{{ $exam->title }}</h1>
                    @include('frontend.partials.detail-header-meta', [
                        'categoryTrail' => $categoryTrail,
                        'categoryUrlFn' => fn ($category) => route('frontend.categories.show', $category),
                        'publishedLabel' => $publishedLabel ? 'Published '.$publishedLabel : null,
                        'publishedDatetime' => optional($publishedAt)?->toIso8601String(),
                    ])
                </div>
            </div>
        </header>

        <div class="et-container et-exam-show__shell et-exam-show__body">
            <div class="et-exam-show__layout">
                <main class="et-exam-show__main">
                    <section class="et-exam-show__section et-exam-show__about" aria-labelledby="exam-about-heading">
                        <div class="et-exam-show__section-head">
                            <span class="et-exam-show__section-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M12 8h.01M11 12h1v4h1m8-4a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            </span>
                            <div>
                                <p>Overview</p>
                                <h2 id="exam-about-heading">About this exam</h2>
                            </div>
                        </div>
                        <div class="et-prose">
                            @if($hasDescription)
                                <x-rich-text-content :content="$exam->description" />
                            @else
                                <p>No description provided for this exam.</p>
                            @endif
                        </div>
                    </section>

<section class="et-exam-show__section" aria-labelledby="exam-details-heading">
                        <div class="et-exam-show__section-head">
                            <span class="et-exam-show__section-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M9 5h10M9 12h10M9 19h10M5 5h.01M5 12h.01M5 19h.01"/></svg>
                            </span>
                            <div>
                                <p>Key information</p>
                                <h2 id="exam-details-heading">Exam details</h2>
                            </div>
                        </div>

                        <dl class="et-exam-show__details">
                            <div><dt>Exam mode</dt><dd>{{ $modeLabel }}</dd></div>
                            <div><dt>Question types</dt><dd>{{ $formats ?: 'Not specified' }}</dd></div>
                            <div><dt>Pricing</dt><dd>{{ $priceLabel }}</dd></div>
                            <div><dt>Attempts allowed</dt><dd>{{ $attemptsLabel }}</dd></div>
                            <div><dt>Language</dt><dd>{{ strtoupper((string) ($exam->language ?: 'en')) }}</dd></div>
                            <div><dt>Timezone</dt><dd>{{ $exam->timezone ?: config('app.timezone') }}</dd></div>
                            <div><dt>Warnings allowed</dt><dd>{{ $warningLimit }}</dd></div>
                            <div class="et-exam-show__detail-wide">
                                <dt>Schedule</dt>
                                <dd>
                                    @if(($exam->schedule_type ?? 'any_time') === 'fixed_window')
                                        {{ optional($exam->scheduled_start)->format('d M Y, H:i') ?: 'Start pending' }}
                                        <span aria-hidden="true">—</span>
                                        {{ optional($exam->scheduled_end)->format('d M Y, H:i') ?: 'End pending' }}
                                    @else
                                        Available any time
                                    @endif
                                </dd>
                            </div>
                            <div class="et-exam-show__detail-wide">
                                <dt>Registration deadline</dt>
                                <dd>{{ optional($exam->registration_deadline)->format('d M Y, H:i') ?: 'No deadline' }}</dd>
                            </div>
                        </dl>
                    </section>

                    @auth
                        @include('frontend.exam.partials.previous-attempts', [
                            'exam' => $exam,
                            'previousAttempts' => $previousAttempts,
                        ])
                    @endauth

                    @include('frontend.exam.partials.feedback', [
                        'exam' => $exam,
                        'feedbackSummary' => $feedbackSummary ?? null,
                        'userFeedback' => $userFeedback ?? null,
                        'canLeaveFeedback' => $canLeaveFeedback ?? false,
                    ])
                </main>

                <aside class="et-exam-show__aside" aria-label="Exam actions and quick information">
                    <section class="et-exam-show__action-card" id="exam-cta" data-return-url="{{ $returnUrl }}">
                        <p class="et-exam-show__action-label">{{ $isFree ? 'Free exam' : 'Premium exam' }}</p>
                        <div class="et-exam-show__price">{{ $priceLabel }}</div>
                        <p class="et-exam-show__action-copy">
                            Review the rules and requirements before starting your attempt.
                        </p>

                        <div class="et-exam-show__actions">
                            @guest
                                <a href="{{ route('login', ['redirect' => $returnUrl]) }}"
                                   class="et-btn et-btn--primary js-store-return"
                                   data-return-url="{{ $returnUrl }}">Login to attempt</a>
                                <a href="{{ route('register', ['redirect' => $returnUrl]) }}"
                                   class="et-btn et-btn--ghost js-store-return"
                                   data-return-url="{{ $returnUrl }}">Create an account</a>
                                @if(! $isFree)
                                    <a href="{{ route('login', ['redirect' => $returnUrl]) }}"
                                       class="et-btn et-btn--ghost js-store-return"
                                       data-return-url="{{ $returnUrl }}">Purchase Exam</a>
                                @endif
                            @else
                                @if(! empty($evaluation['can_continue']) && ! empty($evaluation['active_attempt_id']))
                                    <a href="{{ route('frontend.exams.started', $exam) }}" class="et-btn et-btn--primary">Continue Exam</a>
                                @elseif(empty($evaluation['requires_payment']))
                                    <a href="{{ route('frontend.exams.rules', $exam) }}" class="et-btn et-btn--primary">Attempt Exam</a>
                                @endif

                                @if(! empty($evaluation['requires_payment']))
                                    <button type="button"
                                            class="et-btn et-btn--primary"
                                            id="purchase-exam-btn"
                                            data-exam-purchase
                                            data-url="{{ route('frontend.exams.purchase', $exam) }}"
                                            data-redirect="{{ route('frontend.exams.rules', $exam) }}">Purchase Exam</button>
                                @endif

                                @if($previousAttempts->isNotEmpty())
                                    <a href="#previous-attempts" class="et-btn et-btn--ghost">Previous Attempts</a>
                                @endif
                            @endauth
                        </div>

                        @auth
                            @if(! empty($evaluation['reasons']))
                                <div class="et-exam-show__eligibility">
                                    <strong>Before you continue</strong>
                                    <ul>
                                        @foreach($evaluation['reasons'] as $reason)
                                            <li>{{ $reason }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endauth
                    </section>

                    <section class="et-exam-show__aside-card">
                        <div class="et-exam-show__aside-head">
                            <p>At a glance</p>
                            <h2>Quick facts</h2>
                        </div>
                        <dl class="et-exam-show__facts">
                            <div><dt>Duration</dt><dd>{{ (int) ($exam->duration ?? 0) }} Minutes</dd></div>
                            <div><dt>Questions</dt><dd>{{ (int) ($exam->total_questions ?? 0) }}</dd></div>
                            <div><dt>Total marks</dt><dd>{{ (int) ($exam->total_marks ?? 0) }}</dd></div>
                            <div><dt>Passing marks</dt><dd>{{ (int) ($exam->passing_marks ?? 0) }}</dd></div>
                            <div><dt>Mode</dt><dd>{{ $modeLabel }}</dd></div>
                            <div><dt>Attempts</dt><dd>{{ $attemptsLabel }}</dd></div>
                            <div><dt>Language</dt><dd>{{ strtoupper((string) ($exam->language ?: 'en')) }}</dd></div>
                            <div><dt>Availability</dt><dd>{{ $scheduleLabel }}</dd></div>
                        </dl>
                    </section>

                    @if($examTags->isNotEmpty())
                        <section class="et-exam-show__aside-card">
                            <div class="et-exam-show__aside-head">
                                <p>Topics</p>
                                <h2>Tags</h2>
                            </div>
                            <div class="et-exam-show__tags">
                                @foreach($examTags as $tag)
                                    <span class="et-exam-show__tag">{{ $tag }}</span>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <section class="et-exam-show__notice" role="note">
                        <span class="et-exam-show__notice-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.3 4.4 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.4a2 2 0 0 0-3.4 0Z"/></svg>
                        </span>
                        <div>
                            <h2>Monitoring notice</h2>
                            @if($warningLimit === 0)
                                <p>No warnings are allowed. The first monitored violation can auto-submit your attempt.</p>
                            @else
                                <p>Up to <strong>{{ $warningLimit }}</strong> monitored warning{{ $warningLimit === 1 ? '' : 's' }} may be issued before auto-submission.</p>
                            @endif
                        </div>
                    </section>
                </aside>
            </div>

</div>
    </article>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ versioned_asset('css/frontend/feedback.css') }}">
@endpush

@push('scripts')
<script src="{{ versioned_asset('js/frontend/feedback.js') }}" defer></script>
<script src="{{ versioned_asset('js/frontend/exam-purchase.js') }}" defer></script>
<script src="{{ versioned_asset('js/frontend/exam-show.js') }}" defer></script>
@endpush
