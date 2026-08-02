<div class="px-4 py-5 sm:p-6 space-y-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Custom advertisements</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Create banner, iframe, or HTML advertisements to use in Ads Placement.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="panel-button-secondary text-sm" data-ads-google-create>
                New Google Ad
            </button>
            <button type="button" class="panel-button-primary text-sm" data-ads-create>
                Create advertisement
            </button>
        </div>
    </div>

    <div class="ads-filters" role="search" aria-label="Filter advertisements">
        <div class="ads-filters__search">
            <label class="ads-filters__label" for="ads-filter-search">Search</label>
            <div class="ads-filters__search-wrap">
                <svg class="ads-filters__search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <input
                    type="search"
                    id="ads-filter-search"
                    class="ads-filters__input"
                    placeholder="Search by name or title…"
                    autocomplete="off"
                    data-ads-filter-search
                >
            </div>
        </div>

        <div class="ads-filters__field">
            <label class="ads-filters__label" for="ads-filter-type">Type</label>
            <div class="ads-filters__select-wrap">
                <select
                    id="ads-filter-type"
                    class="ads-filters__select"
                    data-ads-filter-type
                    data-no-search
                    data-disable-search
                    aria-label="Filter by advertisement type"
                >
                    <option value="">All types</option>
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="ads-filters__field">
            <label class="ads-filters__label" for="ads-filter-status">Status</label>
            <div class="ads-filters__select-wrap">
                <select
                    id="ads-filter-status"
                    class="ads-filters__select"
                    data-ads-filter-status
                    data-no-search
                    data-disable-search
                    aria-label="Filter by status"
                >
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div class="ads-filters__actions">
            <button
                type="button"
                class="ads-filters__clear"
                data-ads-filter-clear
                hidden
                aria-label="Clear advertisement filters"
            >
                Clear filters
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-900/60 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Type</th>
                    <th class="px-4 py-3 font-medium">Details</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800" data-ads-table-body>
                {{-- Filled by JS --}}
            </tbody>
        </table>
        <div class="hidden px-4 py-10 text-center text-slate-500 dark:text-slate-400" data-ads-empty>
            No custom advertisements yet. Create a banner, iframe, or HTML ad to get started.
        </div>
    </div>

    <div class="ads-google-section space-y-3 pt-2">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Google Ad configurations</h3>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    AdSense / Google ad units selectable from the Ads Placement tab.
                </p>
            </div>
        </div>
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/60 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Client / Slot</th>
                        <th class="px-4 py-3 font-medium">Format</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800" data-google-ads-table-body>
                </tbody>
            </table>
            <div class="hidden px-4 py-8 text-center text-slate-500 dark:text-slate-400" data-google-ads-empty>
                No Google Ad configurations yet. Create one to place Google Ads on your pages.
            </div>
        </div>
    </div>
</div>
