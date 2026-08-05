{{--
  Shared detail header meta row under the title.
  Expected:
    $categoryTrail — collection of models with name (+ slug or id) OR array items with name/url
    $categoryUrlFn — optional callable(Category $c): string
    $publishedLabel — optional string
    $publishedDatetime — optional ISO datetime for <time>
--}}
@php
    $trail = collect($categoryTrail ?? []);
    $publishedLabel = $publishedLabel ?? null;
    $publishedDatetime = $publishedDatetime ?? null;
    $categoryUrlFn = $categoryUrlFn ?? null;
@endphp

@if($trail->isNotEmpty() || filled($publishedLabel))
    <div class="et-detail-meta">
        @if($trail->isNotEmpty())
            <nav class="et-detail-meta__categories" aria-label="Category">
                @foreach($trail as $trailItem)
                    @php
                        $label = is_array($trailItem) ? ($trailItem['name'] ?? '') : ($trailItem->name ?? '');
                        $url = is_array($trailItem)
                            ? ($trailItem['url'] ?? null)
                            : (is_callable($categoryUrlFn) ? $categoryUrlFn($trailItem) : null);
                    @endphp
                    @continue($label === '')
                    @if(! $loop->first)
                        <span class="et-detail-meta__sep" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
                        </span>
                    @endif
                    @if($url)
                        <a href="{{ $url }}">{{ $label }}</a>
                    @else
                        <span>{{ $label }}</span>
                    @endif
                @endforeach
            </nav>
        @else
            <span></span>
        @endif

        @if(filled($publishedLabel))
            <p class="et-detail-meta__date">
                @if($publishedDatetime)
                    <time datetime="{{ $publishedDatetime }}">{{ $publishedLabel }}</time>
                @else
                    {{ $publishedLabel }}
                @endif
            </p>
        @endif
    </div>
@endif
