@props([
    'page' => null,
    'position' => null,
    'placement' => null,
])

@php
    $html = $placement
        ? ad_slot((string) $placement)
        : ad_slot((string) $page, $position !== null ? (string) $position : null);
@endphp

@if($html !== '')
    {!! $html !!}
@endif
