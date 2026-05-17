@props([
    'label' => '',
    'inputId',
    'name',
    'value' => '',
    'placeholder' => '',
    'height' => 180,
    'required' => false,
    'toolbar' => ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo'],
    'rows' => 6,
    'help' => null,
    'wrapperClass' => '',
])

@php
    $resolvedValue = old($name, $value);
    $hostId = $inputId . '_editor';
@endphp

<div class="rich-editor-block {{ $wrapperClass }}">
    @if($label)
        <label for="{{ $inputId }}" class="exam-label">
            {{ $label }}
            @if($required)
                <span class="form-required">*</span>
            @endif
        </label>
    @endif

    <textarea
        id="{{ $inputId }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        class="rich-editor-input hidden"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
    >{{ $resolvedValue }}</textarea>

    <div
        id="{{ $hostId }}"
        class="editor-shell rich-editor-shell"
        data-rich-editor
        data-editor-input="{{ $inputId }}"
        data-editor-placeholder="{{ $placeholder }}"
        data-editor-height="{{ $height }}"
        data-editor-required="{{ $required ? '1' : '0' }}"
        data-editor-toolbar='@json($toolbar)'
    ></div>

    @if($help)
        <p class="exam-help">{{ $help }}</p>
    @endif
</div>
