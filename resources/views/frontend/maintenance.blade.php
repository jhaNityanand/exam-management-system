<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $maintenance['title'] }} — {{ $maintenance['site_name'] }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) ($maintenance['message'] ?? 'We will be back shortly.')), 160) }}">
    <meta property="og:title" content="{{ $maintenance['title'] }} — {{ $maintenance['site_name'] }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) ($maintenance['message'] ?? 'We will be back shortly.')), 160) }}">
    <meta property="og:image" content="{{ seo_default_image('organization') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ seo_default_image('organization') }}">
    @include('frontend.partials.theme-init')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ versioned_asset('css/frontend/app.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/frontend/maintenance.css') }}">
</head>
<body class="et-body et-maintenance-body">
@php
    $bgStyle = !empty($maintenance['background_url'])
        ? "background-image: linear-gradient(160deg, rgba(2,6,23,.72), rgba(15,23,42,.78)), url('".e($maintenance['background_url'])."');"
        : '';
@endphp
<div class="et-maintenance" @if($bgStyle) style="{{ $bgStyle }}" @endif>
    <div class="et-maintenance__glow" aria-hidden="true"></div>
    <main class="et-maintenance__card">
        @if(!empty($maintenance['logo_url']))
            <img class="et-maintenance__logo" src="{{ $maintenance['logo_url'] }}" alt="{{ $maintenance['site_name'] }}">
        @else
            <p class="et-maintenance__brand">{{ $maintenance['site_name'] }}</p>
        @endif

        <p class="et-maintenance__badge">Maintenance in progress</p>
        <h1 class="et-maintenance__title">{{ $maintenance['title'] }}</h1>
        <p class="et-maintenance__message">{!! nl2br(e($maintenance['message'])) !!}</p>

        @if(!empty($maintenance['estimated_at_formatted']))
            <div class="et-maintenance__eta">
                <span class="et-maintenance__eta-label">Expected back</span>
                <strong>{{ $maintenance['estimated_at_formatted'] }}</strong>
            </div>
        @endif

        @if(!empty($maintenance['contact_email']) || !empty($maintenance['contact_phone']))
            <div class="et-maintenance__contact">
                @if(!empty($maintenance['contact_email']))
                    <a href="mailto:{{ $maintenance['contact_email'] }}">{{ $maintenance['contact_email'] }}</a>
                @endif
                @if(!empty($maintenance['contact_phone']))
                    <a href="tel:{{ preg_replace('/\s+/', '', $maintenance['contact_phone']) }}">{{ $maintenance['contact_phone'] }}</a>
                @endif
            </div>
        @endif

        @if(!empty($maintenance['social_links']))
            <ul class="et-maintenance__social" aria-label="Social links">
                @foreach($maintenance['social_links'] as $link)
                    <li>
                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">{{ $link['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        @endif

        <p class="et-maintenance__footnote">Thank you for your patience.</p>
    </main>
</div>
</body>
</html>
