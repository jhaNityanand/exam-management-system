@props([
    'id' => 'invoice-coming-soon-modal',
    'title' => 'Coming Soon',
    'message' => 'The Invoice feature is currently under development and will be available in a future release.',
    'size' => 'md', // md | lg | xl
])

@php
    $sizeClass = match ($size) {
        'lg' => 'ems-coming-soon-modal__panel--lg',
        'xl' => 'ems-coming-soon-modal__panel--xl',
        default => '',
    };
@endphp

<div id="{{ $id }}" class="ems-coming-soon-modal" hidden>
    <div class="ems-coming-soon-modal__backdrop" data-modal-close></div>
    <div class="ems-coming-soon-modal__panel {{ $sizeClass }}" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
        <div class="ems-coming-soon-modal__icon" aria-hidden="true">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <span class="ems-coming-soon-modal__badge">Coming Soon</span>
        <h3 id="{{ $id }}-title" class="ems-coming-soon-modal__title">{{ $title }}</h3>
        <p class="ems-coming-soon-modal__message">{{ $message }}</p>
        <button type="button" class="panel-button-primary" data-modal-close>Close</button>
    </div>
</div>
