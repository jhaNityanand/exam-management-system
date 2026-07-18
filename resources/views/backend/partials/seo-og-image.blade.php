@php
    $seoOgItem = $item ?? $seoItem ?? null;
    $seoOgImageId = old('og_image_id', $seoOgItem?->og_image_id);
    $seoOgPreviewUrl = $seoOgItem?->ogImage?->file_url;
    if ($seoOgImageId && (int) $seoOgImageId !== (int) ($seoOgItem?->og_image_id ?? 0)) {
        $seoOgPreviewUrl = \App\Models\Gallery::query()
            ->forOrg(current_organization_id())
            ->whereKey($seoOgImageId)
            ->first()
            ?->file_url ?: $seoOgPreviewUrl;
    }
@endphp

{{-- Styles must be emitted here: @push('styles') from content runs after @stack('styles') in the layout head. --}}
@once('seo-og-image-assets')
    <link rel="stylesheet" href="{{ asset('css/backend/gallery-picker.css') }}?v={{ filemtime(public_path('css/backend/gallery-picker.css')) }}">
@endonce

<div class="qcat-meta-field">
    @include('backend.partials.gallery-picker', [
        'name' => 'og_image_id',
        'label' => 'OG Image',
        'multiple' => false,
        'value' => $seoOgImageId,
        'previewUrl' => $seoOgPreviewUrl,
        'kind' => 'image',
    ])
    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
        Recommended size: 1200 × 630 px for social sharing previews.
    </p>
    @error('og_image_id')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
</div>

@once('seo-og-image-scripts')
    <div id="seo-gallery-picker-config"
         data-data-url="{{ route('admin.gallery.data') }}"
         data-store-url="{{ route('admin.gallery.store') }}"
         data-commit-url="{{ route('admin.gallery.commit') }}"
         data-csrf="{{ csrf_token() }}"
         hidden></div>

    @push('scripts')
        <script src="{{ asset('js/backend/content-form-shared.js') }}?v={{ filemtime(public_path('js/backend/content-form-shared.js')) }}"></script>
        <script>
            (() => {
                const init = () => {
                    const config = document.getElementById('seo-gallery-picker-config');
                    if (!config) return;
                    window.galleryDataUrl = config.dataset.dataUrl;
                    window.galleryStoreUrl = config.dataset.storeUrl;
                    window.galleryCommitUrl = config.dataset.commitUrl;
                    window.galleryCsrf = config.dataset.csrf;
                    window.EmsContentForm?.initGalleryPickers({});
                };
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init, { once: true });
                } else {
                    init();
                }
            })();
        </script>
    @endpush
@endonce
