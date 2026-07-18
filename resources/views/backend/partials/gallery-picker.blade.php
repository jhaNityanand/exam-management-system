@props([
    'name' => 'gallery_id',
    'label' => 'Image',
    'multiple' => false,
    'value' => null,
    'kind' => 'image',
    'inputId' => null,
    'previewId' => null,
    'previewUrl' => null,
])

@php
    $inputId = $inputId ?? ($multiple ? $name . '_values' : $name);
    $previewId = $previewId ?? ($multiple ? $name . '_preview' : $name . '_preview');
    $modalId = 'gallery-picker-' . preg_replace('/[^a-z0-9_-]/i', '-', $name);
    $selected = $multiple ? (array) ($value ?? []) : array_filter([(int) $value]);
@endphp

<div class="gallery-picker-field" data-gallery-picker data-name="{{ $name }}" data-multiple="{{ $multiple ? '1' : '0' }}" data-kind="{{ $kind }}" data-modal-id="{{ $modalId }}">
    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ $label }}</label>

    @if ($multiple)
        <div id="{{ $previewId }}" class="gallery-picker-preview gallery-picker-preview--multi flex flex-wrap gap-2 mb-2">
            @foreach ($selected as $gid)
                <div class="gallery-picker-thumb" data-id="{{ $gid }}">
                    <img src="" alt="" class="gallery-picker-thumb__img hidden">
                    <span class="gallery-picker-thumb__placeholder">#{{ $gid }}</span>
                </div>
            @endforeach
        </div>
        <div id="{{ $inputId }}" class="gallery-picker-inputs">
            @foreach ($selected as $gid)
                <input type="hidden" name="{{ $name }}[]" value="{{ $gid }}">
            @endforeach
        </div>
    @else
        <div id="{{ $previewId }}" class="gallery-picker-preview mb-2">
            @if (!empty($selected[0]))
                <div class="gallery-picker-thumb is-selected" data-id="{{ $selected[0] }}">
                    @if ($previewUrl)
                        <img src="{{ $previewUrl }}" alt="" class="gallery-picker-thumb__img">
                    @else
                        <img src="" alt="" class="gallery-picker-thumb__img hidden">
                        <span class="gallery-picker-thumb__placeholder">#{{ $selected[0] }}</span>
                    @endif
                </div>
            @endif
        </div>
        <input type="hidden" id="{{ $inputId }}" name="{{ $name }}" value="{{ $selected[0] ?? '' }}">
    @endif

    <div class="flex flex-wrap gap-2">
        <button type="button" class="gallery-picker-open panel-button-secondary text-sm" data-target="{{ $modalId }}">
            Choose from Gallery
        </button>
        <button type="button" class="gallery-picker-clear panel-button-secondary text-sm" @if(!$multiple && empty($selected[0])) hidden @endif>
            Clear
        </button>
        <label class="gallery-picker-upload panel-button-secondary text-sm cursor-pointer">
            Upload
            <input type="file" class="sr-only gallery-picker-upload-input" accept="{{ $kind === 'image' ? 'image/*' : 'image/*,video/*,.pdf' }}">
        </label>
    </div>

    <div class="gallery-picker-upload-progress" data-gallery-upload-progress hidden>
        <div class="gallery-picker-upload-progress__bar" data-gallery-upload-progress-bar></div>
        <span>Uploading…</span>
    </div>
</div>

<div id="{{ $modalId }}" class="gallery-picker-modal hidden" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="gallery-picker-modal__backdrop" data-close-modal></div>
    <div class="gallery-picker-modal__panel">
        <div class="gallery-picker-modal__header">
            <h3 id="{{ $modalId }}-title" class="text-lg font-semibold text-slate-900 dark:text-white">Select Media</h3>
            <button type="button" class="gallery-picker-modal__close" data-close-modal aria-label="Close">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="gallery-picker-modal__toolbar">
            <input type="search" class="gallery-picker-search panel-input text-sm" placeholder="Search gallery…">
            <button type="button" class="gallery-picker-refresh panel-button-secondary text-sm">Refresh</button>
        </div>
        <div class="gallery-picker-modal__grid" data-grid></div>
        <div class="gallery-picker-modal__footer">
            <button type="button" class="panel-button-secondary" data-close-modal>Cancel</button>
            <button type="button" class="panel-button-primary gallery-picker-confirm">Select</button>
        </div>
    </div>
</div>
