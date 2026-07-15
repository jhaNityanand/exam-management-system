@if(!empty($breadcrumbs) && count($breadcrumbs))
    <nav class="et-breadcrumbs" aria-label="Breadcrumb">
        @foreach($breadcrumbs as $i => $crumb)
            @if($i > 0)
                <span class="et-breadcrumbs__sep" aria-hidden="true">/</span>
            @endif
            @if(!empty($crumb['url']) && !$loop->last)
                <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
            @else
                <span aria-current="{{ $loop->last ? 'page' : 'false' }}">{{ $crumb['label'] }}</span>
            @endif
        @endforeach
    </nav>
@endif
