{{--
  Thin layout helper for pages that already have a main + real sidebar.
  Does not invent empty side columns — only wraps existing content and
  optionally renders shared footer strip when requested.
--}}
@props([
    'page' => null,
    'showAboveFooter' => false,
])

@php
    $pageKey = $page ?: frontend_ad_page_key();
@endphp

{{ $slot }}

@if($showAboveFooter && $pageKey)
    {!! ad_slot($pageKey, 'above_footer') !!}
@endif
