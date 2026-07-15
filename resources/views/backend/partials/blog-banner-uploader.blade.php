@props([
    'name' => 'banner_ids',
    'label' => 'Banner Images',
    'value' => [],
    'items' => [], // [['id'=>1,'url'=> '...','name'=>''], ...]
])

@php
    $selectedIds = array_values(array_filter(array_map('intval', (array) $value)));
    $itemMap = collect($items)->keyBy(fn ($row) => (int) ($row['id'] ?? 0));
@endphp

<div
    class="blog-banner-uploader"
    data-blog-banners
    data-name="{{ $name }}"
    data-commit-url="{{ route('admin.gallery.commit') }}"
    data-gallery-data-url="{{ route('admin.gallery.data') }}"
>
    <div class="flex flex-wrap items-end justify-between gap-2 mb-2">
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ $label }}</label>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Upload one or more images. Drag to reorder. First image is the featured banner.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="panel-button-secondary text-sm" data-banner-choose>Choose from Gallery</button>
            <label class="panel-button-secondary text-sm cursor-pointer">
                Upload images
                <input type="file" accept="image/*" multiple class="sr-only" data-banner-file>
            </label>
        </div>
    </div>

    <div class="blog-banner-dropzone" data-banner-dropzone tabindex="0" role="button" aria-label="Drop banner images here">
        <div class="blog-banner-dropzone__inner">
            <svg class="h-8 w-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Drag &amp; drop banner images here</p>
            <p class="text-xs text-slate-500">JPG, PNG, GIF, WebP only</p>
        </div>
    </div>

    <div class="blog-banner-progress" data-banner-progress hidden>
        <div class="blog-banner-progress__bar" data-banner-progress-bar></div>
        <span data-banner-progress-label>Uploading…</span>
    </div>

    <div class="blog-banner-grid" data-banner-grid>
        @foreach ($selectedIds as $index => $gid)
            @php $item = $itemMap->get($gid); @endphp
            <article class="blog-banner-card" data-banner-id="{{ $gid }}" draggable="true">
                <div class="blog-banner-card__media">
                    @if (!empty($item['url']))
                        <img src="{{ $item['url'] }}" alt="{{ $item['name'] ?? 'Banner' }}">
                    @else
                        <div class="blog-banner-card__placeholder">#{{ $gid }}</div>
                    @endif
                    @if ($index === 0)
                        <span class="blog-banner-card__badge">Featured</span>
                    @endif
                </div>
                <div class="blog-banner-card__actions">
                    <button type="button" class="blog-banner-icon-btn" data-banner-edit title="Edit" aria-label="Edit">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4.586a1 1 0 00.707-.293l9.414-9.414a2 2 0 000-2.828l-2.172-2.172a2 2 0 00-2.828 0L4.293 14.707A1 1 0 004 15.414V20z"/></svg>
                    </button>
                    <button type="button" class="blog-banner-icon-btn blog-banner-icon-btn--danger" data-banner-remove title="Remove" aria-label="Remove">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8"/></svg>
                    </button>
                </div>
                <input type="hidden" name="{{ $name }}[]" value="{{ $gid }}">
            </article>
        @endforeach
    </div>

    <div class="blog-banner-empty" data-banner-empty @if(count($selectedIds)) hidden @endif>
        No banner images yet. Upload or choose from the gallery.
    </div>
</div>
