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
@endphp

@section('content')
<x-ad-layout page="exam_detail">
    <div class="et-page-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Exams', 'url' => route('frontend.exams.index')],
                ['label' => $exam->title],
            ]])
            <div class="et-card__meta et-exam-detail__badges">
                @if($exam->category)
                    <span class="et-badge">{{ $exam->category->name }}</span>
                @endif
                @if($exam->difficulty_level)
                    <span class="et-badge et-badge--slate">{{ ucfirst($exam->difficulty_level) }}</span>
                @endif
                <span class="et-badge">{{ $isFree ? 'Free' : 'Paid' }}</span>
                <span class="et-badge et-badge--slate">{{ ucfirst(str_replace('_', ' ', (string) $exam->visibility)) }}</span>
            </div>
            <h1>{{ $exam->title }}</h1>
            <div class="et-exam-detail__meta-row">
                @if($publishedLabel)
                    <span>Published {{ $publishedLabel }}</span>
                @endif
                @if($exam->duration)
                    <span>{{ (int) $exam->duration }} min</span>
                @endif
                @if($exam->total_questions)
                    <span>{{ (int) $exam->total_questions }} questions</span>
                @endif
                @if($exam->total_marks)
                    <span>{{ (int) $exam->total_marks }} marks</span>
                @endif
            </div>
        </div>
    </div>

    <x-ad-slot page="exam_detail" position="below_title" />

    <div class="et-container et-page-stack">
        <div class="et-grid et-grid--4">
            <div class="et-stat"><span class="et-stat__value">{{ (int) ($exam->duration ?? 0) }}</span><span class="et-stat__label">Minutes</span></div>
            <div class="et-stat"><span class="et-stat__value">{{ (int) ($exam->total_questions ?? 0) }}</span><span class="et-stat__label">Questions</span></div>
            <div class="et-stat"><span class="et-stat__value">{{ (int) ($exam->total_marks ?? 0) }}</span><span class="et-stat__label">Total marks</span></div>
            <div class="et-stat"><span class="et-stat__value">{{ (int) ($exam->passing_marks ?? 0) }}</span><span class="et-stat__label">Passing marks</span></div>
        </div>

        <x-ad-slot page="exam_detail" position="after_stats" />

        <section class="et-card et-exam-detail__about" aria-labelledby="exam-about-heading">
            <h2 id="exam-about-heading">About this exam</h2>
            <div class="et-prose">
                @if($hasDescription)
                    <x-rich-text-content :content="$exam->description" />
                @else
                    <p>No description provided for this exam.</p>
                @endif
            </div>
        </section>

        <x-ad-slot page="exam_detail" position="after_about" />

        <div class="et-card et-card--padded">
            <h2 class="et-heading-flush">Exam details</h2>
            <div class="et-callout et-callout--warning et-warning-limit et-callout--spaced" role="note">
                <strong>Warnings allowed: {{ $warningLimit }}</strong>
                @if($warningLimit === 0)
                    <p>No warnings are allowed during this exam. The first monitored violation can auto-submit your attempt.</p>
                @else
                    <p>Candidates may receive up to <strong>{{ $warningLimit }}</strong> monitored warning{{ $warningLimit === 1 ? '' : 's' }} before the exam is auto-submitted.</p>
                @endif
            </div>
            <div class="et-grid et-grid--2 et-detail-grid">
                <div><strong>Mode:</strong> {{ ucfirst((string) $exam->exam_mode) }}</div>
                <div><strong>Question types:</strong> {{ $formats ?: '—' }}</div>
                <div><strong>Pricing:</strong>
                    @if($isFree)
                        Free
                    @else
                        {{ strtoupper((string) ($exam->exam_currency ?: 'INR')) }} {{ number_format((float) $exam->exam_amount, 2) }}
                    @endif
                </div>
                <div><strong>Attempts allowed:</strong> {{ $attemptsLabel }}</div>
                <div><strong>Language:</strong> {{ strtoupper((string) ($exam->language ?: 'en')) }}</div>
                <div><strong>Timezone:</strong> {{ $exam->timezone ?: config('app.timezone') }}</div>
                <div><strong>Published:</strong> {{ $publishedLabel ?: '—' }}</div>
                <div><strong>Warnings allowed:</strong> {{ $warningLimit }}</div>
                <div><strong>Schedule:</strong>
                    @if(($exam->schedule_type ?? 'any_time') === 'fixed_window')
                        {{ optional($exam->scheduled_start)->format('d M Y H:i') ?: '—' }}
                        —
                        {{ optional($exam->scheduled_end)->format('d M Y H:i') ?: '—' }}
                    @else
                        Available any time
                    @endif
                </div>
                <div><strong>Registration deadline:</strong>
                    {{ optional($exam->registration_deadline)->format('d M Y H:i') ?: 'None' }}
                </div>
            </div>
        </div>

        <x-ad-slot page="exam_detail" position="after_details" />

        <div class="et-card et-exam-detail__cta" id="exam-cta" data-return-url="{{ $returnUrl }}">
            @guest
                <a href="{{ route('login', ['redirect' => $returnUrl]) }}"
                   class="et-btn et-btn--primary js-store-return"
                   data-return-url="{{ $returnUrl }}">Login to attempt</a>
                <a href="{{ route('register', ['redirect' => $returnUrl]) }}"
                   class="et-btn et-btn--ghost js-store-return"
                   data-return-url="{{ $returnUrl }}">Register</a>
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
                    <a href="#previous-attempts" class="et-btn et-btn--ghost">View Previous Attempts</a>
                @endif
            @endauth

            @auth
                @if(! empty($evaluation['reasons']))
                    <ul class="et-cta-reasons">
                        @foreach($evaluation['reasons'] as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                @endif
            @endauth
        </div>

        <x-ad-slot page="exam_detail" position="after_cta" />

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

        <x-ad-slot page="exam_detail" position="after_content" />
    </div>

    {{-- Full-width layout: keep related exams in-flow (fixed rail overlaps et-container). --}}
    @include('frontend.partials.detail-sidebar', ['detailSidebarInline' => true])
</x-ad-layout>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ versioned_asset('css/frontend/feedback.css') }}">
@endpush

@push('scripts')
<script src="{{ versioned_asset('js/frontend/feedback.js') }}" defer></script>
<script src="{{ versioned_asset('js/frontend/exam-purchase.js') }}" defer></script>
<script src="{{ versioned_asset('js/frontend/exam-show.js') }}" defer></script>
@endpush
