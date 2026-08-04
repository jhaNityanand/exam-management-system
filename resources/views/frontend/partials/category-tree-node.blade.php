{{--
  Recursive category tree node.
  Expected: $node (array from CategoryTreeService), $depth (int)
--}}
@php
    $depth = $depth ?? 0;
    $hasChildren = ! empty($node['has_children']);
    $isExpanded = (bool) ($node['is_expanded'] ?? false);
    $isActive = (bool) ($node['is_active'] ?? false);
    $childrenId = 'et-cat-tree-children-'.($node['id'] ?? uniqid());
    $count = (int) ($node['count'] ?? 0);
@endphp

<li
    class="et-cat-tree__node{{ $hasChildren ? ' has-children' : '' }}{{ $isExpanded ? ' is-open' : '' }}{{ $isActive ? ' is-active' : '' }}"
    data-cat-tree-node
    role="treeitem"
    @if($hasChildren) aria-expanded="{{ $isExpanded ? 'true' : 'false' }}" @endif
    @if($isActive) aria-current="page" @endif
>
    <div class="et-cat-tree__row" style="--et-cat-depth: {{ $depth }}">
        @if($hasChildren)
            <button
                type="button"
                class="et-cat-tree__expander"
                data-cat-tree-expander
                aria-label="{{ $isExpanded ? 'Collapse' : 'Expand' }} {{ $node['name'] }}"
                aria-controls="{{ $childrenId }}"
            >
                <svg class="et-cat-tree__chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        @else
            <span class="et-cat-tree__leaf" aria-hidden="true"></span>
        @endif

        <a
            class="et-cat-tree__link"
            href="{{ $node['url'] }}"
            @if($isActive) aria-current="page" @endif
        >
            <span class="et-cat-tree__name">{{ $node['name'] }}</span>
            <span class="et-cat-tree__count" title="{{ $count }} {{ \Illuminate\Support\Str::plural('post', $count) }}">
                {{ number_format($count) }}
            </span>
        </a>
    </div>

    @if($hasChildren)
        <div
            class="et-cat-tree__children"
            id="{{ $childrenId }}"
            role="group"
            data-cat-tree-children
            @unless($isExpanded) inert @endunless
        >
            <div class="et-cat-tree__children-inner">
                <ul class="et-cat-tree__list">
                    @foreach($node['children'] as $child)
                        @include('frontend.partials.category-tree-node', ['node' => $child, 'depth' => $depth + 1])
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</li>
