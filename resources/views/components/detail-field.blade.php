@props([
    'label',
    'value' => null,
    'empty' => 'Not Set',
    'span' => 1,
    'href' => null,
])

@php
    $isEmpty = display_value($value, '') === '';
    $display = display_value($value, $empty);
    $spanClass = $span > 1 ? 'md:col-span-'.$span : '';
@endphp

<div {{ $attributes->class(['detail-field', $spanClass]) }}>
    <dt class="detail-field__label">{{ $label }}</dt>
    <dd class="detail-field__value {{ $isEmpty ? 'detail-field__value--empty' : '' }}">
        @if ($slot->isNotEmpty())
            {{ $slot }}
        @elseif ($href && ! $isEmpty)
            <a href="{{ $href }}" target="_blank" rel="noopener noreferrer" class="detail-field__link">{{ $display }}</a>
        @else
            {{ $display }}
        @endif
    </dd>
</div>
