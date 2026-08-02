@php
    $sidebar = $detailSidebar ?? null;
    $items = collect($sidebar['items'] ?? []);
@endphp

@if($sidebar && $items->isNotEmpty())
    <aside class="et-detail-rail" aria-label="{{ $sidebar['title'] ?? 'Related content' }}">
        <div class="et-detail-sidebar">
            <div class="et-detail-sidebar__head">
                <div class="et-detail-sidebar__copy">
                    @if(! empty($sidebar['eyebrow']))
                        <p class="et-detail-sidebar__eyebrow">{{ $sidebar['eyebrow'] }}</p>
                    @endif
                    <h2 class="et-detail-sidebar__title">{{ $sidebar['title'] ?? 'More to explore' }}</h2>
                </div>
                @if(! empty($sidebar['view_all_url']) && ! empty($sidebar['view_all_label']))
                    <a class="et-detail-sidebar__all" href="{{ $sidebar['view_all_url'] }}">{{ $sidebar['view_all_label'] }}</a>
                @endif
            </div>

            <ul class="et-detail-sidebar__list">
                @foreach($items as $item)
                    <li>
                        <a class="et-detail-sidebar__item" href="{{ $item['url'] }}">
                            <span class="et-detail-sidebar__thumb" aria-hidden="true">
                                @if(! empty($item['image']))
                                    <img src="{{ $item['image'] }}" alt="" loading="lazy" width="96" height="72">
                                @else
                                    <span class="et-detail-sidebar__thumb-fallback"></span>
                                @endif
                            </span>
                            <span class="et-detail-sidebar__body">
                                @if(! empty($item['kicker']))
                                    <span class="et-detail-sidebar__kicker">{{ $item['kicker'] }}</span>
                                @endif
                                <span class="et-detail-sidebar__item-title">{{ $item['title'] }}</span>
                                @if(! empty($item['meta']))
                                    <span class="et-detail-sidebar__meta">{{ $item['meta'] }}</span>
                                @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </aside>
@endif
