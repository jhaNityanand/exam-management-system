@php
    $index = $GLOBALS['nodeIndex']++;
    $id = "node-" . $index;
    $colors = ['#4f46e5', '#0f766e', '#d97706', '#dc2626', '#7c3aed', '#2563eb'];
@endphp
<div class="category-node{{ $level > 0 ? ' category-node--nested' : '' }} is-entering is-visible"
     data-node-id="{{ $id }}"
     data-level="{{ $level }}"
     style="--tree-color: {{ $colors[$level] ?? '#4f46e5' }}">

    <div class="category-node__card">
        <div class="category-node__level">
            <span class="category-node__badge">L{{ $level }}</span>
        </div>

        <div class="category-node__field category-node__name">
            <label class="category-node__label" for="category-name-{{ $id }}">
                Name <span class="category-node__required">*</span>
            </label>
            <input
                id="category-name-{{ $id }}"
                type="text"
                name="categories[{{ $id }}][name]"
                class="category-node__input"
                placeholder="{{ $level === 0 ? 'Enter category name' : 'Enter child category name' }}"
                value="{{ old('categories.' . $id . '.name', $node->name) }}"
            >
            <p class="category-node__error" aria-live="polite">Name is required.</p>
        </div>

        <div class="category-node__field category-node__description">
            <label class="category-node__label" for="category-description-{{ $id }}">Description</label>
            <textarea
                id="category-description-{{ $id }}"
                rows="2"
                name="categories[{{ $id }}][description]"
                class="category-node__textarea"
                placeholder="Add a short description"
            >{{ old('categories.' . $id . '.description', $node->description) }}</textarea>
        </div>

        {{-- Hidden DB ID field --}}
        <input type="hidden" name="categories[{{ $id }}][id]" value="{{ $node->id }}">

        <div class="category-node__actions">
            <button type="button" class="category-node__btn add-child-btn" aria-label="Add child category" title="Add child">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            </button>
            @if ($level > 0)
                <button type="button" class="category-node__icon remove-node-btn" aria-label="Delete category" title="Remove category">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            @endif
        </div>
    </div>
    <div class="category-node__children">
        @foreach ($node->childrenRecursive as $child)
            @include('backend.blog-categories.partials.edit-tree-node', [
                'node'  => $child,
                'level' => $level + 1,
            ])
        @endforeach
    </div>
</div>
