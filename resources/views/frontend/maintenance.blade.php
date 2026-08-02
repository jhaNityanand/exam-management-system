@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => $maintenance['title'] ?? 'Maintenance',
        'description' => $maintenance['message_plain'] ?? 'We will be back shortly.',
        'robots' => 'noindex, nofollow',
        'image_type' => 'organization',
    ];
    $hasCountdown = ! empty($maintenance['estimated_at_iso']);
    $hasSocial = ! empty($maintenance['social_links']);
@endphp

@section('content')
    <div class="et-page-hero et-maintenance-hero">
        <div class="et-container">
            <p class="et-maintenance__badge">
                <span class="et-maintenance__badge-dot" aria-hidden="true"></span>
                Maintenance in progress
            </p>
            <h1>{{ $maintenance['title'] }}</h1>
            @if(! empty($maintenance['estimated_at_formatted']))
                <p class="et-maintenance__eta-line">
                    Expected back <strong>{{ $maintenance['estimated_at_formatted'] }}</strong>
                </p>
            @endif
        </div>
    </div>

    <div class="et-container et-page-stack et-maintenance">
        <section class="et-card et-maintenance__panel" aria-labelledby="maintenance-heading">
            <h2 id="maintenance-heading" class="et-visually-hidden">{{ $maintenance['title'] }}</h2>

            <div class="et-maintenance__layout{{ $hasCountdown ? ' et-maintenance__layout--split' : '' }}">
                <div class="et-maintenance__primary">
                    @if(! empty($maintenance['message']))
                        @php
                            $messageHtml = (string) $maintenance['message'];
                            $messageIsHtml = $messageHtml !== strip_tags($messageHtml);
                        @endphp
                        <div class="et-maintenance__message et-prose ems-rich-content">
                            @if($messageIsHtml)
                                {!! $messageHtml !!}
                            @else
                                {!! nl2br(e($messageHtml)) !!}
                            @endif
                        </div>
                    @endif

                    <p class="et-maintenance__footnote">Thank you for your patience.</p>
                </div>

                @if($hasCountdown)
                    <aside class="et-maintenance__aside" aria-label="Countdown">
                        <div class="et-maintenance__countdown"
                             data-maintenance-countdown
                             data-restore-at="{{ $maintenance['estimated_at_iso'] }}"
                             aria-live="polite">
                            <p class="et-maintenance__countdown-label">Back online in</p>
                            <div class="et-maintenance__countdown-grid" role="timer">
                                <div class="et-maintenance__countdown-unit">
                                    <span class="et-maintenance__countdown-value" data-unit="days">00</span>
                                    <span class="et-maintenance__countdown-caption">Days</span>
                                </div>
                                <div class="et-maintenance__countdown-unit">
                                    <span class="et-maintenance__countdown-value" data-unit="hours">00</span>
                                    <span class="et-maintenance__countdown-caption">Hours</span>
                                </div>
                                <div class="et-maintenance__countdown-unit">
                                    <span class="et-maintenance__countdown-value" data-unit="minutes">00</span>
                                    <span class="et-maintenance__countdown-caption">Minutes</span>
                                </div>
                                <div class="et-maintenance__countdown-unit">
                                    <span class="et-maintenance__countdown-value" data-unit="seconds">00</span>
                                    <span class="et-maintenance__countdown-caption">Seconds</span>
                                </div>
                            </div>
                            <p class="et-maintenance__countdown-done" data-countdown-done hidden>
                                We should be back online now. Refresh the page to continue.
                            </p>
                        </div>
                    </aside>
                @endif
            </div>

            @if($hasSocial)
                <div class="et-maintenance__social-wrap">
                    <p class="et-maintenance__social-label">Stay connected</p>
                    <ul class="et-maintenance__social" aria-label="Social links">
                        @foreach($maintenance['social_links'] as $link)
                            <li>
                                <a href="{{ $link['url'] }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="et-maintenance__social-link"
                                   aria-label="{{ $link['label'] }}">
                                    <span class="et-maintenance__social-icon" aria-hidden="true">
                                        @include('backend.partials.social-platform-icon', ['platform' => $link['platform'], 'size' => 16])
                                    </span>
                                    <span>{{ $link['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/frontend/maintenance.css') }}">
@endpush

@push('scripts')
    @if($hasCountdown)
        <script src="{{ versioned_asset('js/frontend/maintenance-countdown.js') }}" defer></script>
    @endif
@endpush
