{{-- Single advertisement unit (Google / custom). Markup comes from DB — no decorative chrome. --}}
<div class="et-ad-unit et-ad-unit--{{ $source }}" data-ad-name="{{ $name }}">
    @if(! empty($css))
        <style>{!! $css !!}</style>
    @endif
    {!! $html !!}
    @if(! empty($js))
        <script>{!! $js !!}</script>
    @endif
</div>
