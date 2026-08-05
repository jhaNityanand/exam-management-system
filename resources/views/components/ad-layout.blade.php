{{-- Frontend ads disabled during UI redesign. Passes page content through unchanged. --}}
@props([
    'page' => 'home',
    'showRails' => true,
    'showAboveFooter' => true,
])

{{ $slot }}
