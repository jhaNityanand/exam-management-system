@props([
    'name' => 'gallery_id',
    'label' => 'Image',
    'multiple' => false,
    'value' => null,
    'kind' => 'image',
    'inputId' => null,
    'previewId' => null,
    'previewUrl' => null,
    'recommendKey' => null,
    'recommendWidth' => null,
    'recommendHeight' => null,
    'recommendHint' => null,
])

@php
    $inputId = $inputId ?? ($multiple ? $name . '_values' : $name);
    $previewId = $previewId ?? ($multiple ? $name . '_preview' : $name . '_preview');
    $modalId = 'gallery-picker-' . preg_replace('/[^a-z0-9_-]+/i', '-', $name);
    $selected = $multiple ? (array) ($value ?? []) : array_filter([(int) $value]);
    $accept = $kind === 'image' ? 'image/*' : 'image/*,video/*,.pdf';

    $guide = $recommendKey ? \App\Support\ImageSizeGuide::get($recommendKey) : null;
    $recWidth = (int) ($recommendWidth ?? ($guide['width'] ?? 0));
    $recHeight = (int) ($recommendHeight ?? ($guide['height'] ?? 0));
    $recHint = $recommendHint
        ?? ($guide['hint'] ?? null)
        ?? ($recWidth > 0 && $recHeight > 0
            ? sprintf('Recommended size: %d × %d px.', $recWidth, $recHeight)
            : null);
    $recSizeLabel = $recWidth > 0 && $recHeight > 0
        ? sprintf('%d × %d px', $recWidth, $recHeight)
        : '';
@endphp

<div
    class="gallery-picker-field"
    data-gallery-picker
    data-name="{{ $name }}"
    data-multiple="{{ $multiple ? '1' : '0' }}"
    data-kind="{{ $kind }}"
    data-modal-id="{{ $modalId }}"
    @if($recWidth > 0) data-recommend-width="{{ $recWidth }}" @endif
    @if($recHeight > 0) data-recommend-height="{{ $recHeight }}" @endif
    @if($recSizeLabel !== '') data-recommend-label="{{ $recSizeLabel }}" @endif
>
    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ $label }}</label>

    <div
        class="gallery-picker-dropzone"
        data-gallery-dropzone
        tabindex="0"
        role="button"
        aria-label="Upload {{ $multiple ? 'files' : 'a file' }}"
    >
        <div class="gallery-picker-dropzone__inner">
            <svg class="gallery-picker-dropzone__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="gallery-picker-dropzone__title">Drag &amp; drop {{ $multiple ? 'files' : 'a file' }} here</p>
            <p class="gallery-picker-dropzone__hint">or click to browse · stored in Gallery</p>
            @if($recHint)
                <p class="gallery-picker-dropzone__size">{{ $recHint }}</p>
            @endif
        </div>
    </div>

    @if ($multiple)
        <div id="{{ $previewId }}" class="gallery-picker-preview gallery-picker-preview--multi" data-gallery-preview>
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
        <p class="gallery-picker-empty" data-gallery-empty @if(count($selected)) hidden @endif>No files selected yet.</p>
    @else
        <div id="{{ $previewId }}" class="gallery-picker-preview" data-gallery-preview>
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
        <p class="gallery-picker-empty" data-gallery-empty @if(!empty($selected[0])) hidden @endif>No file selected yet.</p>
    @endif

    @if($recHint)
        <p class="gallery-picker-size-hint">{{ $recHint }}</p>
    @endif

    <div class="gallery-picker-toolbar">
        <button type="button" class="gallery-picker-open panel-button-secondary text-sm" data-target="{{ $modalId }}">
            Choose from Gallery
        </button>
        <button type="button" class="gallery-picker-clear panel-button-secondary text-sm" @if($multiple ? !count($selected) : empty($selected[0])) hidden @endif>
            Clear
        </button>
        <label class="gallery-picker-upload panel-button-secondary text-sm cursor-pointer">
            Upload
            <input
                type="file"
                class="sr-only gallery-picker-upload-input"
                accept="{{ $accept }}"
                @if ($multiple) multiple @endif
            >
        </label>
    </div>

    <div class="gallery-picker-upload-progress" data-gallery-upload-progress hidden>
        <div class="gallery-picker-upload-progress__bar" data-gallery-upload-progress-bar></div>
        <span data-gallery-upload-progress-label>Uploading…</span>
    </div>
</div>

<div id="{{ $modalId }}" class="gallery-picker-modal hidden" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="gallery-picker-modal__backdrop" data-close-modal></div>
    <div class="gallery-picker-modal__panel">
        <div class="gallery-picker-modal__header">
            <div class="gallery-picker-modal__heading">
                <h3 id="{{ $modalId }}-title" class="text-lg font-semibold text-slate-900 dark:text-white">Select Media</h3>
                @if($recSizeLabel !== '')
                    <p class="gallery-picker-modal__recommend" data-modal-recommend>
                        Recommended for this field: <strong>{{ $recSizeLabel }}</strong>
                        — matching images are highlighted.
                    </p>
                @endif
            </div>
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
