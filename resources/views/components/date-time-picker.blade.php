@php
    $formatted = $value;
    if ($value instanceof \Carbon\CarbonInterface) {
        $formatted = match ($mode) {
            'date' => $value->format('Y-m-d'),
            'time' => $value->format('H:i'),
            default => $value->format('Y-m-d H:i'),
        };
    }
    $placeholder = $placeholder ?: match ($mode) {
        'date' => 'Select date…',
        'time' => 'Select time…',
        default => 'Select date & time…',
    };
    $displayFormat = match ($mode) {
        'date' => 'M j, Y',
        'time' => 'h:i K',
        default => 'M j, Y h:i K',
    };
    $altFormat = match ($mode) {
        'date' => 'Y-m-d',
        'time' => 'H:i',
        default => 'Y-m-d H:i',
    };
@endphp

<div class="ems-dtp {{ $wrapperClass }}" data-ems-datetime data-mode="{{ $mode }}">
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="ems-dtp__control">
        <input
            type="text"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $formatted }}"
            class="{{ $inputClass }} ems-dtp__input mt-1 block w-full"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            data-ems-datetime-input
            data-enable-time="{{ $enableTime ? '1' : '0' }}"
            data-no-calendar="{{ $mode === 'time' ? '1' : '0' }}"
            data-date-format="{{ $altFormat }}"
            data-alt-format="{{ $displayFormat }}"
            @if ($required) required @endif
            {{ $attributes->except(['class', 'name', 'id', 'value'])->merge([]) }}
        >
        <button type="button" class="ems-dtp__icon" data-ems-datetime-toggle tabindex="-1" aria-label="Open calendar">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </button>
    </div>

    @if ($help)
        <p class="mt-1.5 text-xs text-slate-400 dark:text-slate-500">{{ $help }}</p>
    @endif
</div>
