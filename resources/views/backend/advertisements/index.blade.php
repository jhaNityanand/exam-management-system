@extends('backend.layouts.app')

@section('title', 'Advertisements')
@section('page-title', 'Advertisements')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Settings', 'url' => route('admin.settings.index')],
        ['label' => 'Advertisements'],
    ]" />
@endsection

@section('content')
@php
    $initialTab = in_array(request()->query('tab'), ['placement', 'ads', 'custom-code'], true)
        ? request()->query('tab')
        : 'placement';
@endphp

<div
    id="ads-module"
    class="ads-module space-y-6"
    x-data="{
        tab: (['placement','ads','custom-code'].includes((window.location.hash || '').replace('#',''))
            ? (window.location.hash || '').replace('#','')
            : @js($initialTab))
    }"
>
    <x-page-card>
        <div class="ads-module__header px-4 py-5 sm:px-6 border-b border-slate-100 dark:border-slate-800">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Advertisement management</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Place ads on frontend pages, manage custom creatives, and configure global header/footer ad scripts.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="panel-button-secondary text-sm" data-ads-help-open>
                        Help &amp; documentation
                    </button>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2" role="tablist" aria-label="Advertisement sections">
                @foreach([
                    'placement' => 'Ads Placement',
                    'ads' => 'Advertisements',
                    'custom-code' => 'Custom Code',
                ] as $id => $label)
                    <button
                        type="button"
                        @click="tab = '{{ $id }}'; window.location.hash = '{{ $id }}'"
                        :class="tab === '{{ $id }}' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'"
                        class="rounded-lg border px-3 py-1.5 text-sm font-medium transition"
                        role="tab"
                        :aria-selected="tab === '{{ $id }}'"
                    >{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <div x-show="tab === 'placement'" x-cloak class="ads-tab" data-ads-tab="placement">
            @include('backend.advertisements.partials.tab-placement')
        </div>

        <div x-show="tab === 'ads'" x-cloak class="ads-tab" data-ads-tab="ads">
            @include('backend.advertisements.partials.tab-ads')
        </div>

        <div x-show="tab === 'custom-code'" x-cloak class="ads-tab" data-ads-tab="custom-code">
            @include('backend.advertisements.partials.tab-custom-code')
        </div>
    </x-page-card>
</div>

@include('backend.advertisements.partials.modals')
@include('backend.advertisements.partials.help-modal')
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/backend/gallery-picker.css') }}?v={{ filemtime(public_path('css/backend/gallery-picker.css')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
    <link rel="stylesheet" href="{{ asset('css/backend/gallery.css') }}?v={{ filemtime(public_path('css/backend/gallery.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/components/ems-dialog.css') }}?v={{ filemtime(public_path('css/components/ems-dialog.css')) }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/advertisements.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
    <script src="{{ versioned_asset('js/backend/gallery-editor.js') }}"></script>
    <script>
        window.galleryDataUrl = @json($galleryDataUrl);
        window.galleryStoreUrl = @json($galleryStoreUrl);
        window.galleryCommitUrl = @json($galleryCommitUrl);
        window.galleryCsrf = @json(csrf_token());
        window.contentFormConfig = {
            formId: 'ad-form',
            module: 'advertisements',
            existingMedia: {},
            skipFormSubmitHook: true,
        };
        window.adsModuleConfig = {
            csrf: @json(csrf_token()),
            pageKey: @json($pageKey),
            pages: @json($pages),
            positions: @json($positions),
            types: @json($types),
            bannerSizes: @json($bannerSizes),
            multiPositions: @json($multiPositions),
            ads: @json($adsPayload),
            googleAds: @json($googleAdsPayload),
            placements: @json($placementsPayload),
            customCode: @json($customCode),
            routes: {
                placementsIndex: @json(route('admin.advertisements.placements.index')),
                placementsStore: @json(route('admin.advertisements.placements.store')),
                placementsUpdate: @json(url('/admin/advertisements/placements')),
                placementsDestroy: @json(url('/admin/advertisements/placements')),
                adsStore: @json(route('admin.advertisements.store')),
                adsUpdate: @json(url('/admin/advertisements')),
                adsDestroy: @json(url('/admin/advertisements')),
                googleStore: @json(route('admin.advertisements.google.store')),
                googleUpdate: @json(url('/admin/advertisements/google')),
                googleDestroy: @json(url('/admin/advertisements/google')),
                customCode: @json(route('admin.advertisements.custom-code')),
            },
        };
    </script>
    <script src="{{ versioned_asset('js/backend/content-form-shared.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/advertisements.js') }}"></script>
@endpush
