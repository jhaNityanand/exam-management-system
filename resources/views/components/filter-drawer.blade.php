@props([
    'title',
    'subtitle' => null,
])

<aside
    id="filter-drawer"
    class="offcanvas-drawer"
    tabindex="-1"
    aria-labelledby="filter-drawer-title"
    aria-hidden="true"
>
    <div class="offcanvas-header">
        <div>
            <h2 class="offcanvas-title" id="filter-drawer-title">{{ $title }}</h2>
            @if ($subtitle)
                <p class="offcanvas-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        <button type="button" class="offcanvas-close" aria-label="Close filters">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <form id="filter-drawer-form" class="offcanvas-form">
        <div class="offcanvas-body">
            {{ $slot }}
        </div>
        <div class="offcanvas-footer">
            <button type="reset" class="panel-button-secondary">Reset</button>
            <button type="submit" class="panel-button-primary">Apply Filters</button>
        </div>
    </form>
</aside>
