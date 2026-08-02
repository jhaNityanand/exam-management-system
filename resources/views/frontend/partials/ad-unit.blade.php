{{-- Single advertisement unit (Google / custom). Markup comes from DB. --}}
<div class="et-ad-unit et-ad-unit--{{ $source }}" data-ad-name="{{ $name }}">
    @if(! empty($css))
        <style>{!! $css !!}</style>
    @endif
    <div class="et-ad-unit__body">
        {!! $html !!}
    </div>
    @if(! empty($js))
        <script>{!! $js !!}</script>
    @endif
</div>
