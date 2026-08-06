@props([
    'page' => null,
    'position' => null,
    'placement' => null,
])

@php
    $pageKey = $page ?: frontend_ad_page_key();
    $positionKey = $position ?: $placement;
@endphp

@if($pageKey && $positionKey)
    {!! ad_slot($pageKey, $positionKey) !!}
@endif
