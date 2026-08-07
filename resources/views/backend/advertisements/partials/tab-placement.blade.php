@php
    $currentPage = $pages[$pageKey] ?? $pages['home'];
@endphp

<div class="px-4 py-5 sm:p-6 space-y-5">
    <div class="ads-placement-toolbar">
        <div class="ads-placement-toolbar__field">
            <label for="ads-page-select" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                Page selection
            </label>
            <select id="ads-page-select" class="panel-input w-full sm:max-w-md" data-ads-page-select>
                @foreach($pagesGrouped as $group => $groupPages)
                    <optgroup label="{{ $group }}">
                        @foreach($groupPages as $key => $label)
                            <option value="{{ $key }}" @selected($key === $pageKey)>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400" data-ads-page-description>
                {{ $currentPage['description'] ?? '' }}
            </p>
        </div>
        <div class="ads-placement-toolbar__legend" aria-hidden="true">
            <span class="ads-legend ads-legend--empty">Insert line</span>
            <span class="ads-legend ads-legend--filled">Ad placed</span>
            <span class="ads-legend ads-legend--disabled">Disabled</span>
        </div>
    </div>

    <div class="ads-preview-shell">
        <div class="ads-preview-shell__bar">
            <div class="ads-preview-shell__dots" aria-hidden="true">
                <span></span><span></span><span></span>
            </div>
            <p class="ads-preview-shell__url" data-ads-preview-title>{{ $currentPage['label'] ?? 'Home' }} preview</p>
        </div>

        <div class="ads-preview" data-ads-preview data-layout="{{ $currentPage['layout'] ?? 'home' }}">
            {{-- Filled dynamically by advertisements.js --}}
            <div class="ads-preview__loading text-sm text-slate-500 dark:text-slate-400 p-8 text-center">
                Loading placement preview…
            </div>
        </div>
    </div>

    <p class="text-xs text-slate-500 dark:text-slate-400">
        Each page preview mirrors the live frontend layout for that page.
        Click the centered <strong>+</strong> after any main section — or after each real sidebar section — to place an ad.
        Only one placement action appears between the navbar and page title.
        Side columns appear only when the real page has them (for example, Categories on list pages; left tools on FAQs/legal/account).
        Blog and News detail previews also show insert lines <strong>before each H2</strong>.
        Dashed boxes show placed ads; a new <strong>+</strong> stays below. All placements are inside the same centered page container.
        Frontend rendering is live and dynamic — configure and manage your advertisement placements here to control live ads across the site instantly.
    </p>
</div>
