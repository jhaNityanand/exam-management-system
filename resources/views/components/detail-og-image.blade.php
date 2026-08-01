@props([
    'label' => 'OG Image',
    'image' => null,
    'empty' => 'Not Set',
])

<div {{ $attributes->class(['detail-field', 'md:col-span-2']) }}>
    <dt class="detail-field__label">{{ $label }}</dt>
    <dd class="detail-field__value {{ $image ? '' : 'detail-field__value--empty' }}">
        @if ($image && filled($image->file_url ?? null))
            <img
                src="{{ $image->file_url }}"
                alt="{{ $image->alt_text ?: ($image->original_name ?: 'OG image') }}"
                class="detail-field__image mt-1"
            >
        @else
            {{ $empty }}
        @endif
    </dd>
</div>
