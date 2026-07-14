@props([
    'label' => '',
    'inputId' => null,
    'name',
    'value' => '',
    'placeholder' => '',
    'height' => 280,
    'required' => false,
    'preset' => 'full',
    'toolbar' => null,
    'rows' => 8,
    'help' => null,
    'wrapperClass' => '',
    'readonly' => false,
])

@php
    $inputId = $inputId ?: str_replace(['[', ']'], ['_', ''], $name) . '_field';
    $resolvedValue = old($name, $value);
    $presetToolbars = config('editor.toolbar_presets', []);
    $resolvedToolbar = $toolbar ?? ($presetToolbars[$preset] ?? $presetToolbars['full'] ?? '');
    $uploadUrl = route('admin.editor.media.store');
    $cdnBase = rtrim((string) config('editor.cdn.base_url'), '/');
@endphp

<div
    class="ems-rich-editor {{ $wrapperClass }}"
    data-ems-rich-editor
    data-editor-input="{{ $inputId }}"
    data-editor-placeholder="{{ $placeholder }}"
    data-editor-height="{{ (int) $height }}"
    data-editor-required="{{ $required ? '1' : '0' }}"
    data-editor-preset="{{ $preset }}"
    data-editor-toolbar="{{ $resolvedToolbar }}"
    data-editor-upload-url="{{ $uploadUrl }}"
    data-editor-cdn-base="{{ $cdnBase }}"
    data-editor-readonly="{{ $readonly ? '1' : '0' }}"
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
            placeholder="{{ $placeholder }}"
            data-ems-rich-textarea
            @if($required) required @endif
            @if($readonly) readonly @endif
        >{{ $resolvedValue }}</textarea>
        <div class="ems-rich-editor__progress" hidden data-editor-progress>
            <div class="ems-rich-editor__progress-bar" data-editor-progress-bar></div>
            <span class="ems-rich-editor__progress-label" data-editor-progress-label>Uploading…</span>
        </div>
    </div>

    @if($help)
        <p class="exam-help">{{ $help }}</p>
    @endif
</div>
