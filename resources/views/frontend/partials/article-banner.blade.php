{{--
  Article / detail banner with optional auto-slide.
  Expected: $images (list<string>), $alt (optional)
--}}
@php
    $images = array_values(array_filter($images ?? []));
    $alt = $alt ?? '';
    $hasSlider = count($images) > 1;
@endphp
@if(count($images) > 0)
    <figure
        class="et-article-banner et-article-banner--fit{{ $hasSlider ? ' et-banner-slider' : '' }}"
        @if($hasSlider) data-banner-slider data-interval="3000" @endif
    >
        @foreach($images as $index => $src)
            <div
                class="et-banner-slider__slide{{ $index === 0 ? ' is-active' : '' }}"
                data-banner-slide
                @if($index > 0) aria-hidden="true" @endif
            >
                <img
                    src="{{ $src }}"
                    alt="{{ $alt }}"
                    loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                    width="1600"
                    height="900"
                >
            </div>
        @endforeach
        @if($hasSlider)
            <div class="et-banner-slider__dots" role="tablist" aria-label="Banner images">
                @foreach($images as $index => $src)
                    <button
                        type="button"
                        class="et-banner-slider__dot{{ $index === 0 ? ' is-active' : '' }}"
                        data-banner-dot
                        aria-label="Show image {{ $index + 1 }}"
                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                    ></button>
                @endforeach
            </div>
        @endif
    </figure>
@endif
