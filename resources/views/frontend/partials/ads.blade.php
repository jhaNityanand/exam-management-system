{{-- Renders one or more ads for a placement --}}
@php
    /** @var \Illuminate\Support\Collection|\App\Models\Cms\Advertisement[] $ads */
    $ads = $ads ?? collect();
    $placement = $placement ?? '';
@endphp
@foreach($ads as $ad)
    <aside class="et-ad" data-ad-placement="{{ $placement }}" data-ad-type="{{ $ad->type }}" aria-label="Advertisement">
        <p class="et-ad__label">Advertisement</p>

        @if($ad->type === 'banner')
            @php
                $desktop = $ad->image?->file_url;
                $mobile = $ad->mobileImage?->file_url ?: $desktop;
                $href = $ad->cta_url;
            @endphp
            @if($desktop || $ad->headline)
                @if($href)
                    <a href="{{ $href }}" class="et-ad__banner" target="_blank" rel="noopener sponsored nofollow">
                        @if($desktop)
                            <img class="et-ad__img et-ad__img--desktop" src="{{ $desktop }}" alt="{{ $ad->headline ?: $ad->name }}" loading="lazy">
                        @endif
                        @if($mobile)
                            <img class="et-ad__img et-ad__img--mobile" src="{{ $mobile }}" alt="{{ $ad->headline ?: $ad->name }}" loading="lazy">
                        @endif
                        @if($ad->headline || $ad->body || $ad->cta_label)
                            <span class="et-ad__copy">
                                @if($ad->headline)<strong>{{ $ad->headline }}</strong>@endif
                                @if($ad->body)<span>{{ $ad->body }}</span>@endif
                                @if($ad->cta_label)<em>{{ $ad->cta_label }}</em>@endif
                            </span>
                        @endif
                    </a>
                @else
                    <div class="et-ad__banner">
                        @if($desktop)
                            <img class="et-ad__img et-ad__img--desktop" src="{{ $desktop }}" alt="{{ $ad->headline ?: $ad->name }}" loading="lazy">
                        @endif
                        @if($mobile)
                            <img class="et-ad__img et-ad__img--mobile" src="{{ $mobile }}" alt="{{ $ad->headline ?: $ad->name }}" loading="lazy">
                        @endif
                    </div>
                @endif
            @endif
        @elseif(in_array($ad->type, ['google_ads', 'custom_html', 'iframe'], true))
            <div class="et-ad__code">
                {!! $ad->code !!}
            </div>
        @endif
    </aside>
@endforeach
