{{--
  Card media with optional auto-slide.
  Expected: $images (list<string>), $href, $alt (optional)
--}}
@php
    $images = array_values(array_filter($images ?? []));
    $href = $href ?? '#';
    $alt = $alt ?? '';
    $hasSlider = count($images) > 1;
@endphp
@if(count($images) > 0)
    <div
        class="et-card__media et-card__media--fit{{ $hasSlider ? ' et-banner-slider' : '' }}"
        @if($hasSlider) data-banner-slider data-interval="3000" @endif
    >
        @foreach($images as $index => $src)
            <div
                class="et-banner-slider__slide{{ $index === 0 ? ' is-active' : '' }}"
                data-banner-slide
                @if($index > 0) aria-hidden="true" @endif
            >
                <a href="{{ $href }}" class="et-banner-slider__link" tabindex="-1" aria-hidden="true">
                    <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy">
                </a>
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
    </div>
@endif
