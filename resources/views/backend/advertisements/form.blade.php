@extends('backend.layouts.app')

@php
    $isEdit = (bool) $ad;
@endphp

@section('title', $isEdit ? 'Edit advertisement' : 'Create advertisement')
@section('page-title', $isEdit ? 'Edit advertisement' : 'Create advertisement')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Advertisements', 'url' => route('admin.advertisements.index')],
        ['label' => $isEdit ? 'Edit' : 'Create'],
    ]" />
@endsection

@section('content')
<x-page-card class="overflow-visible">
    <form id="advertisement-form" method="POST"
          action="{{ $isEdit ? route('admin.advertisements.update', $ad) : route('admin.advertisements.store') }}"
          class="category-builder">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="category-builder__header px-4 py-6 sm:px-6 border-b border-slate-100 dark:border-slate-800">
            <h1 class="category-builder__title text-slate-900 dark:text-white">{{ $isEdit ? 'Edit advertisement' : 'Create advertisement' }}</h1>
            <p class="category-builder__subtitle text-slate-500">Configure type, placement, creative, and schedule.</p>
        </div>

        <div class="px-4 py-5 sm:p-6 space-y-8" x-data="{ type: @js(old('type', $ad->type ?? 'banner')) }">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" required maxlength="160" class="panel-input mt-1 block w-full"
                           value="{{ old('name', $ad->name ?? '') }}" placeholder="Homepage sidebar promo">
                    @error('name')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Type <span class="text-red-500">*</span></label>
                    <select id="type" name="type" class="panel-input mt-1 block w-full" x-model="type" required>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status <span class="text-red-500">*</span></label>
                    <select id="status" name="status" class="panel-input mt-1 block w-full" required>
                        @foreach(['active','inactive','draft'] as $st)
                            <option value="{{ $st }}" @selected(old('status', $ad->status ?? 'active') === $st)>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label for="placement" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Placement <span class="text-red-500">*</span></label>
                    <select id="placement" name="placement" class="panel-input mt-1 block w-full" required>
                        @foreach($placementGroups as $group)
                            <optgroup label="{{ $group['label'] }}">
                                @foreach($group['slots'] as $key => $label)
                                    <option value="{{ $key }}" @selected(old('placement', $ad->placement ?? '') === $key)>{{ $label }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-slate-500">Use the placement map on the ads index to see where each slot appears.</p>
                </div>
            </div>

            {{-- Code types --}}
            <div x-show="type !== 'banner'" x-cloak class="space-y-3">
                <label for="code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    <span x-text="type === 'google_ads' ? 'Google AdSense code' : (type === 'iframe' ? 'Iframe HTML' : 'Custom HTML / JavaScript')"></span>
                    <span class="text-red-500">*</span>
                </label>
                <textarea id="code" name="code" rows="8" class="panel-input mt-1 block w-full font-mono text-sm"
                          placeholder="<script>...</script> or <iframe ...></iframe>">{{ old('code', $ad->code ?? '') }}</textarea>
                <p class="text-xs text-slate-500">Paste the full snippet. Scripts will render on the public site for this placement.</p>
                @error('code')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
            </div>

            {{-- Banner fields --}}
            <div x-show="type === 'banner'" x-cloak class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="headline" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Headline</label>
                        <input type="text" id="headline" name="headline" class="panel-input mt-1 block w-full" value="{{ old('headline', $ad->headline ?? '') }}">
                    </div>
                    <div>
                        <label for="cta_label" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">CTA label</label>
                        <input type="text" id="cta_label" name="cta_label" class="panel-input mt-1 block w-full" value="{{ old('cta_label', $ad->cta_label ?? '') }}" placeholder="Learn more">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="body" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Body text</label>
                        <textarea id="body" name="body" rows="2" class="panel-input mt-1 block w-full">{{ old('body', $ad->body ?? '') }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="cta_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Destination URL</label>
                        <input type="url" id="cta_url" name="cta_url" class="panel-input mt-1 block w-full" value="{{ old('cta_url', $ad->cta_url ?? '') }}" placeholder="https://…">
                        <p class="mt-1.5 text-xs text-slate-500">Banner click-through URL.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div>
                        @include('backend.partials.gallery-picker', [
                            'name' => 'image_id',
                            'label' => 'Desktop image',
                            'value' => old('image_id', $ad->image_id ?? null),
                            'previewUrl' => $ad->image->file_url ?? null,
                            'kind' => 'image',
                        ])
                        <p class="mt-1.5 text-xs text-slate-500">Recommended <strong>728×90</strong> or <strong>970×250</strong> for leaderboards; <strong>300×250</strong> for sidebars.</p>
                    </div>
                    <div>
                        @include('backend.partials.gallery-picker', [
                            'name' => 'mobile_image_id',
                            'label' => 'Mobile image',
                            'value' => old('mobile_image_id', $ad->mobile_image_id ?? null),
                            'previewUrl' => $ad->mobileImage->file_url ?? null,
                            'kind' => 'image',
                        ])
                        <p class="mt-1.5 text-xs text-slate-500">Recommended <strong>320×100</strong> or <strong>300×250</strong>. Falls back to desktop image if empty.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 border-t border-slate-100 dark:border-slate-800 pt-6">
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sort order</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" class="panel-input mt-1 block w-full" value="{{ old('sort_order', $ad->sort_order ?? 1) }}">
                </div>
                <div>
                    <x-date-time-picker
                        name="starts_at"
                        id="starts_at"
                        mode="datetime"
                        label="Starts at"
                        :value="old('starts_at', optional($ad->starts_at ?? null)->format('Y-m-d H:i'))"
                    />
                </div>
                <div>
                    <x-date-time-picker
                        name="ends_at"
                        id="ends_at"
                        mode="datetime"
                        label="Ends at"
                        :value="old('ends_at', optional($ad->ends_at ?? null)->format('Y-m-d H:i'))"
                    />
                </div>
            </div>
        </div>

        <div class="category-builder__footer px-4 py-4 sm:px-6 bg-slate-50 dark:bg-slate-900/50 flex justify-end gap-3 rounded-b-2xl">
            <a href="{{ route('admin.advertisements.index') }}" class="panel-button-secondary">Cancel</a>
            <button type="submit" class="panel-button-primary">{{ $isEdit ? 'Update advertisement' : 'Create advertisement' }}</button>
        </div>
    </form>
</x-page-card>

@include('backend.partials.image-editor-modal')
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/backend/gallery-picker.css') }}?v={{ filemtime(public_path('css/backend/gallery-picker.css')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
    <link rel="stylesheet" href="{{ asset('css/backend/gallery.css') }}?v={{ filemtime(public_path('css/backend/gallery.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/components/datetime-picker.css') }}?v={{ filemtime(public_path('css/components/datetime-picker.css')) }}">
    <style>[x-cloak]{display:none!important}</style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
    <script src="{{ asset('js/backend/gallery-editor.js') }}?v={{ filemtime(public_path('js/backend/gallery-editor.js')) }}"></script>
    <script>
        window.galleryDataUrl = @json(route('admin.gallery.data'));
        window.galleryStoreUrl = @json(route('admin.gallery.store'));
        window.galleryCommitUrl = @json(route('admin.gallery.commit'));
        window.galleryCsrf = @json(csrf_token());
        window.contentFormConfig = { formId: 'advertisement-form', module: 'advertisement', existingMedia: {}, skipFormSubmitHook: true };
    </script>
    <script src="{{ asset('js/components/datetime-picker.js') }}?v={{ filemtime(public_path('js/components/datetime-picker.js')) }}"></script>
    <script src="{{ asset('js/backend/content-form-shared.js') }}?v={{ filemtime(public_path('js/backend/content-form-shared.js')) }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => window.EmsContentForm?.initGalleryPickers?.({}));
    </script>
@endpush
