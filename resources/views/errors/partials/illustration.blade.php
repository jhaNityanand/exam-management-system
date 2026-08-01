@php
    $code = (string) ($code ?? '404');
@endphp

<svg class="et-err-illu et-err-illu--{{ $code }}" viewBox="0 0 320 240" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
    <rect class="et-err-illu__bg" x="16" y="16" width="288" height="208" rx="28"/>

    @switch($code)
        @case('403')
            <circle class="et-err-illu__ring" cx="160" cy="108" r="58"/>
            <circle class="et-err-illu__ring-soft" cx="160" cy="108" r="72"/>
            <path class="et-err-illu__shape" d="M160 62c18 10 34 12 48 12v34c0 28-18 48-48 58-30-10-48-30-48-58V74c14 0 30-2 48-12z"/>
            <path class="et-err-illu__accent" d="M142 108h36a8 8 0 018 8v22a8 8 0 01-8 8h-36a8 8 0 01-8-8v-22a8 8 0 018-8z"/>
            <path class="et-err-illu__stroke" d="M150 108v-10a10 10 0 1120 0v10"/>
            <circle class="et-err-illu__dot" cx="160" cy="122" r="3.5"/>
            <rect class="et-err-illu__base" x="96" y="186" width="128" height="14" rx="7"/>
            @break

        @case('419')
            <circle class="et-err-illu__ring" cx="160" cy="112" r="54"/>
            <circle class="et-err-illu__ring-soft" cx="160" cy="112" r="68"/>
            <circle class="et-err-illu__face" cx="160" cy="112" r="40"/>
            <path class="et-err-illu__stroke" d="M160 88v28l18 10" stroke-linecap="round" stroke-linejoin="round"/>
            <path class="et-err-illu__accent" d="M160 62v12M160 150v12M112 112h-12M220 112h-12" stroke-linecap="round"/>
            <rect class="et-err-illu__base" x="96" y="186" width="128" height="14" rx="7"/>
            @break

        @case('429')
            <circle class="et-err-illu__ring-soft" cx="160" cy="110" r="70"/>
            <path class="et-err-illu__shape" d="M96 140c0-36 28-64 64-64s64 28 64 64"/>
            <path class="et-err-illu__stroke" d="M160 140V88" stroke-linecap="round"/>
            <circle class="et-err-illu__dot" cx="160" cy="140" r="6"/>
            <path class="et-err-illu__accent" d="M118 150h84" stroke-linecap="round"/>
            <rect class="et-err-illu__base" x="96" y="186" width="128" height="14" rx="7"/>
            @break

        @case('500')
            <circle class="et-err-illu__ring" cx="160" cy="108" r="50"/>
            <circle class="et-err-illu__ring-soft" cx="160" cy="108" r="66"/>
            <path class="et-err-illu__stroke" d="M160 84v36" stroke-linecap="round"/>
            <circle class="et-err-illu__dot" cx="160" cy="136" r="4"/>
            <path class="et-err-illu__accent" d="M108 168h104" stroke-linecap="round"/>
            <rect class="et-err-illu__base" x="96" y="186" width="128" height="14" rx="7"/>
            @break

        @case('503')
            <rect class="et-err-illu__shape" x="118" y="72" width="84" height="64" rx="12"/>
            <path class="et-err-illu__stroke" d="M140 96h40M140 112h28" stroke-linecap="round"/>
            <path class="et-err-illu__accent" d="M132 148l-18 24h92l-18-24"/>
            <circle class="et-err-illu__dot" cx="160" cy="104" r="5"/>
            <rect class="et-err-illu__base" x="96" y="186" width="128" height="14" rx="7"/>
            @break

        @default
            {{-- 404 and fallback --}}
            <circle class="et-err-illu__ring" cx="148" cy="108" r="46"/>
            <circle class="et-err-illu__ring-soft" cx="148" cy="108" r="60"/>
            <circle class="et-err-illu__face" cx="148" cy="108" r="28"/>
            <path class="et-err-illu__stroke" d="M182 142l28 28" stroke-linecap="round"/>
            <path class="et-err-illu__accent" d="M132 100h.01M164 100h.01M132 124c8 8 24 8 32 0" stroke-linecap="round"/>
            <rect class="et-err-illu__base" x="96" y="186" width="128" height="14" rx="7"/>
    @endswitch
</svg>
