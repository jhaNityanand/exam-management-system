{{--
  Render a catalog advertisement slot for the current or given page.
  Usage: @include('frontend.partials.ad-placement', ['position' => 'after_hero'])
         @include('frontend.partials.ad-placement', ['page' => 'blog_detail', 'position' => 'right_after_tags'])
--}}
@php
    $adPage = $page ?? ($adPage ?? frontend_ad_page_key());
    $adPosition = $position ?? ($placement ?? null);
@endphp

@if($adPage && $adPosition)
    {!! ad_slot($adPage, $adPosition) !!}
@endif
