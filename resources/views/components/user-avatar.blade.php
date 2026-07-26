@props([
    'user' => null,
    'name' => null,
    'url' => null,
    'size' => 'md', // sm | md | lg
    'alt' => null,
])

@php
    $avatar = user_avatar($user, $name);
    $imageUrl = $url ?? $avatar['url'];
    $initials = $avatar['initials'];
    $color = $avatar['color'];
    $label = $alt ?? ($avatar['name'].' avatar');
    $sizeClass = match ($size) {
        'sm' => 'ua-avatar--sm',
        'lg' => 'ua-avatar--lg',
        default => 'ua-avatar--md',
    };
@endphp

<span {{ $attributes->class(['ua-avatar', $sizeClass]) }}
      style="--ua-bg: {{ $color }}"
      title="{{ $avatar['name'] }}"
      aria-label="{{ $label }}">
    @if($imageUrl)
        <img src="{{ $imageUrl }}" alt="" loading="lazy" decoding="async">
    @else
        <span class="ua-avatar__initials" aria-hidden="true">{{ $initials }}</span>
    @endif
</span>
