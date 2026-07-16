@props([
    'label' => '',
    'inputId' => null,
    'name',
    'value' => '',
    'placeholder' => 'Write a description, or type / for commands…',
    'height' => 280,
    'required' => false,
    'preset' => 'header',
    'mode' => 'header',
    'toolbar' => null,
    'rows' => 8,
    'help' => null,
    'wrapperClass' => '',
    'readonly' => false,
    'module' => 'editor',
    'showHint' => true,
])

@php
    $inputId = $inputId ?: str_replace(['[', ']'], ['_', ''], $name) . '_field';
    $resolvedValue = old($name, $value);
    $uiMode = $mode ?: config('editor.ui_mode', 'header');
    // Leave blank unless explicitly overridden — the JS side already knows
    // the right toolbar for each mode/preset (config/editor.php stays in
    // sync for reference, but isn't the runtime source of truth here).
    $resolvedToolbar = $toolbar ?? '';
    $uploadUrl = route('admin.editor.media.store');
    $cdnBase = rtrim((string) config('editor.cdn.base_url'), '/');
    $resolvedPlaceholder = $placeholder !== '' ? $placeholder : 'Write a description, or type / for commands…';
@endphp

<div
    class="ems-rich-editor ems-rich-editor--{{ $uiMode }} {{ $wrapperClass }}"
    data-ems-rich-editor
    data-editor-input="{{ $inputId }}"
    data-editor-placeholder="{{ $resolvedPlaceholder }}"
    data-editor-height="{{ (int) $height }}"
    data-editor-required="{{ $required ? '1' : '0' }}"
    data-editor-preset="{{ $preset }}"
    data-editor-mode="{{ $uiMode }}"
    data-editor-toolbar="{{ $resolvedToolbar }}"
    data-editor-upload-url="{{ $uploadUrl }}"
    data-editor-cdn-base="{{ $cdnBase }}"
    data-editor-readonly="{{ $readonly ? '1' : '0' }}"
    data-editor-module="{{ $module }}"
    data-editor-max-image-kb="{{ (int) config('editor.max_image_kb', 2048) }}"
    data-editor-max-video-kb="{{ (int) config('editor.max_video_kb', 20480) }}"
    data-editor-max-file-kb="{{ (int) config('editor.max_file_kb', 10240) }}"
>
    @if($label)
        <label for="{{ $inputId }}" class="exam-label ems-rich-editor__label">
            {{ $label }}
            @if($required)
                <span class="form-required">*</span>
            @endif
        </label>
    @endif

    <div class="ems-rich-editor__surface">
        <textarea
            id="{{ $inputId }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            class="panel-input ems-rich-editor__textarea"
            placeholder="{{ $resolvedPlaceholder }}"
            data-ems-rich-textarea
            @if($required) required @endif
            @if($readonly) readonly @endif
        >{{ $resolvedValue }}</textarea>
        <div class="ems-rich-editor__progress" hidden aria-hidden="true" data-editor-progress>
            <div class="ems-rich-editor__progress-bar" data-editor-progress-bar></div>
            <span class="ems-rich-editor__progress-label" data-editor-progress-label>Uploading…</span>
        </div>
        @if($showHint && $uiMode !== 'compact')
            <div class="ems-rich-editor__hint" data-editor-hint>
                <span>Type <kbd>/</kbd> for commands</span>
                <span class="ems-rich-editor__hint-sep" aria-hidden="true">·</span>
                <span><kbd>@</kbd> to mention someone</span>
            </div>
        @endif
    </div>

    @if($help)
        <p class="exam-help">{{ $help }}</p>
    @endif
</div>
