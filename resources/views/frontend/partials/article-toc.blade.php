@php
    $tocItems = $tocItems ?? [];
@endphp

@if(count($tocItems) >= 2)
    <details class="et-toc" data-article-toc>
        <summary class="et-toc__summary" aria-expanded="false">
            <span class="et-toc__summary-main">
                <svg class="et-toc__icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false">
                    <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
                </svg>
                <strong class="et-toc__title">On this page</strong>
            </span>
            <span class="et-toc__meta">
                <span class="et-toc__count">{{ count($tocItems) }}</span>
                <span class="et-toc__chevron" aria-hidden="true"></span>
            </span>
        </summary>
        <nav class="et-toc__nav" aria-label="Table of contents">
            <ol class="et-toc__list">
                @foreach($tocItems as $item)
                    <li class="et-toc__item et-toc__item--h{{ $item['level'] }}">
                        <a href="#{{ $item['id'] }}" data-toc-link>
                            <span class="et-toc__num" aria-hidden="true">{{ $item['number'] }}</span>
                            <span class="et-toc__text">{{ $item['text'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </nav>
    </details>
@endif
