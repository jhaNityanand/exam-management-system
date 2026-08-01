{{-- Shared category list filter drawer. --}}
@php
    $creators = $creators ?? [];
    $createdBy = array_map('strval', (array) ($createdBy ?? []));
@endphp
<x-filter-drawer
    title="{{ $title ?? 'Filter Categories' }}"
    subtitle="{{ $subtitle ?? 'Narrow the category tree by status, creator, and date' }}"
>
    <div class="filter-group">
        <label for="status-filter" class="filter-label">Status</label>
        <select
            name="status"
            id="status-filter"
            class="panel-input w-full text-sm filter-date-preset"
            data-no-search
            data-placeholder="Select status"
            aria-label="Filter by status"
        >
            <option value="">All Statuses</option>
            @foreach (['active', 'inactive', 'suspended'] as $s)
                <option value="{{ $s }}" @selected(($status ?? '') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>

    <div class="filter-group">
        <label for="created-by-filter" class="filter-label">Created By</label>
        <select
            id="created-by-filter"
            name="created_by[]"
            multiple
            data-filter-multiple
            data-placeholder="Select creators"
            data-max-options="200"
            aria-label="Filter by creator"
        >
            @foreach ($creators as $creator)
                <option value="{{ $creator->id }}" @selected(in_array((string) $creator->id, $createdBy, true))>
                    {{ $creator->name }}
                </option>
            @endforeach
        </select>
        <p class="filter-hint">Select one or more users who created categories.</p>
    </div>

    <x-filter-date-range
        id="category-created"
        label="Created Date"
        from-name="created_from"
        to-name="created_to"
        :from-value="$createdFrom ?? null"
        :to-value="$createdTo ?? null"
    />
</x-filter-drawer>
