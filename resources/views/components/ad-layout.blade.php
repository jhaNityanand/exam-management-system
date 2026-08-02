@props([
    'page' => 'home',
    'showRails' => true,
    'showAboveFooter' => true,
])

@php
    $pageKey = (string) $page;
    $leftHtml = $showRails ? ad_slot($pageKey, 'left_sidebar') : '';
    $rightHtml = $showRails ? ad_slot($pageKey, 'right_sidebar') : '';
    $hasLeft = $leftHtml !== '';
    $hasRight = $rightHtml !== '';
    $shellClass = 'et-ad-layout';
    if ($hasLeft && $hasRight) {
        $shellClass .= ' et-ad-layout--both';
    } elseif ($hasLeft) {
        $shellClass .= ' et-ad-layout--left-only';
    } elseif ($hasRight) {
        $shellClass .= ' et-ad-layout--right-only';
    } else {
        $shellClass .= ' et-ad-layout--single';
    }
@endphp

<div {{ $attributes->class([$shellClass]) }} data-ad-page="{{ $pageKey }}">
    @if($hasLeft)
        <aside class="et-ad-layout__rail et-ad-layout__rail--left" aria-label="Left advertisements">
            {!! $leftHtml !!}
        </aside>
    @endif

    <div class="et-ad-layout__main">
        {{ $slot }}
    </div>

    @if($hasRight)
        <aside class="et-ad-layout__rail et-ad-layout__rail--right" aria-label="Right advertisements">
            {!! $rightHtml !!}
        </aside>
    @endif
</div>

@if($showAboveFooter)
    <x-ad-slot :page="$pageKey" position="above_footer" />
@endif
