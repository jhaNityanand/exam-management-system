@props([
    'name',
    'id' => null,
    'label' => '',
    'value' => '',
    'placeholder' => '#0f766e',
    'required' => false,
    'help' => null,
    'wrapperClass' => '',
    'inputClass' => 'panel-input',
])

@php
    $id = $id ?: str_replace(['[', ']'], ['_', ''], $name);
    $value = old($name, $value);
    $normalized = preg_match('/^#?[0-9a-fA-F]{6}$/', (string) $value)
        ? (str_starts_with((string) $value, '#') ? (string) $value : '#'.$value)
        : ($placeholder ?: '#0f766e');
@endphp

<div {{ $attributes->class(['ems-color-picker', $wrapperClass]) }} data-ems-color-picker>
    @if($label !== '')
        <label for="{{ $id }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="ems-color-picker__row">
        <input
            type="color"
            class="ems-color-picker__swatch"
            value="{{ $normalized }}"
            data-ems-color-swatch
            aria-label="{{ $label !== '' ? $label : 'Pick color' }}"
            tabindex="-1"
        >
        <input
            type="text"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $value }}"
            class="{{ $inputClass }} ems-color-picker__input mt-0 block w-full"
            placeholder="{{ $placeholder }}"
            maxlength="20"
            autocomplete="off"
            spellcheck="false"
            data-ems-color-input
            @if($required) required @endif
        >
    </div>

    @if($help)
        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">{{ $help }}</p>
    @endif
</div>
