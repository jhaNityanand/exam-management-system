{{--
  Category navigation sidebar / mobile accordion.
  Expected: $categoryNav (from CategoryTreeService), optional overrides via $categoryNavTitle / $categoryNavDescription
--}}
@php
    $nav = $categoryNav ?? null;
    $navTitle = $categoryNavTitle ?? ($nav['title'] ?? 'Subcategories');
    $navDescription = $categoryNavDescription ?? ($nav['description'] ?? 'Browse nested topics in this category.');
    $contextName = $nav['context_name'] ?? null;
    $roots = collect($nav['roots'] ?? []);
@endphp

@if($nav && $roots->isNotEmpty())
    <aside class="et-cat-nav" data-cat-nav aria-label="{{ $navTitle }}">
        <button
            type="button"
            class="et-cat-nav__toggle"
            data-cat-nav-toggle
            aria-expanded="false"
            aria-controls="et-cat-nav-panel"
        >
            <span class="et-cat-nav__toggle-copy">
                <span class="et-cat-nav__toggle-label">{{ $navTitle }}</span>
                <span class="et-cat-nav__toggle-hint">
                    {{ $contextName ? 'Under '.$contextName : 'Browse nested topics' }}
                </span>
            </span>
            <span class="et-cat-nav__toggle-meta">
                <span class="et-cat-nav__toggle-count">{{ $roots->count() }}</span>
                <span class="et-cat-nav__toggle-icon" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            </span>
        </button>

        <div class="et-cat-nav__panel" id="et-cat-nav-panel" data-cat-nav-panel>
            <div class="et-cat-nav__card">
                <header class="et-cat-nav__head">
                    @if($contextName)
                        <p class="et-cat-nav__eyebrow">In {{ $contextName }}</p>
                    @endif
                    <h2 class="et-cat-nav__title">{{ $navTitle }}</h2>
                    <p class="et-cat-nav__desc">{{ $navDescription }}</p>
                </header>

                <nav class="et-cat-tree" data-cat-tree aria-label="{{ $navTitle }}">
                    <ul class="et-cat-tree__list" role="tree">
                        @foreach($roots as $node)
                            @include('frontend.partials.category-tree-node', ['node' => $node, 'depth' => 0])
                        @endforeach
                    </ul>
                </nav>
            </div>
        </div>
    </aside>
@endif
