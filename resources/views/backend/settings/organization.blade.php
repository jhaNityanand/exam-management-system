@extends('backend.layouts.app')

@section('title', 'Organization Settings')
@section('page-title', 'Organization Settings')
@section('content-container-class', 'max-w-6xl')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Settings', 'url' => route('admin.settings.index')],
        ['label' => 'Organization'],
    ]" />
@endsection

@section('content')
@php
    $s = $payload['settings'];
    $social = $payload['social'];
    $heroes = $payload['heroes'];
@endphp

<div x-data="{ tab: 'branding' }" class="space-y-6">
    <x-page-card>
        <div class="px-4 py-5 sm:px-6 border-b border-slate-100 dark:border-slate-800">
            <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Organization Settings</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Control branding, contact details, social profiles, and homepage content without code changes.
            </p>
            <div class="mt-4 flex flex-wrap gap-2" role="tablist" aria-label="Organization settings sections">
                @foreach([
                    'branding' => 'Branding',
                    'contact' => 'Contact',
                    'social' => 'Social media',
                    'homepage' => 'Homepage',
                ] as $id => $label)
                    <button type="button"
                            @click="tab = '{{ $id }}'"
                            :class="tab === '{{ $id }}' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'"
                            class="rounded-lg border px-3 py-1.5 text-sm font-medium transition"
                            role="tab">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <form id="organization-settings-form" class="category-builder" novalidate>
            @csrf

            {{-- Branding --}}
            <div x-show="tab === 'branding'" x-cloak class="px-4 py-5 sm:p-6 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="sm:col-span-2">
                        <label for="site_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Organization name <span class="text-red-500">*</span></label>
                        <input type="text" id="site_name" name="site_name" required maxlength="120" value="{{ $s['site_name'] }}" class="panel-input mt-1 block w-full" placeholder="Examtube.in">
                        <p class="qcat-field-error" data-error-for="site_name" hidden></p>
                    </div>
                    <div>
                        <label for="tagline" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tagline</label>
                        <input type="text" id="tagline" name="tagline" maxlength="255" value="{{ $s['tagline'] }}" class="panel-input mt-1 block w-full" placeholder="Practice smarter. Score higher.">
                        <p class="qcat-field-error" data-error-for="tagline" hidden></p>
                    </div>
                    <div>
                        <label for="logo_text" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Logo text fallback</label>
                        <input type="text" id="logo_text" name="logo_text" maxlength="80" value="{{ $s['logo_text'] }}" class="panel-input mt-1 block w-full" placeholder="Examtube">
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Used when no logo image is selected.</p>
                        <p class="qcat-field-error" data-error-for="logo_text" hidden></p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
                        <textarea id="description" name="description" rows="3" maxlength="2000" class="panel-input mt-1 block w-full" placeholder="Short organization description…">{{ $s['description'] }}</textarea>
                        <p class="qcat-field-error" data-error-for="description" hidden></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div>
                        @include('backend.partials.gallery-picker', [
                            'name' => 'logo_gallery_id',
                            'label' => 'Logo',
                            'value' => $s['logo_gallery_id'],
                            'previewUrl' => $s['logo_url'],
                            'kind' => 'image',
                        ])
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Recommended <strong>400×120</strong> px transparent PNG/WebP.</p>
                        <p class="qcat-field-error" data-error-for="logo_gallery_id" hidden></p>
                    </div>
                    <div>
                        @include('backend.partials.gallery-picker', [
                            'name' => 'favicon_gallery_id',
                            'label' => 'Favicon',
                            'value' => $s['favicon_gallery_id'],
                            'previewUrl' => $s['favicon_url'],
                            'kind' => 'image',
                        ])
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Recommended <strong>512×512</strong> px square PNG.</p>
                        <p class="qcat-field-error" data-error-for="favicon_gallery_id" hidden></p>
                    </div>
                    <div>
                        @include('backend.partials.gallery-picker', [
                            'name' => 'og_image_gallery_id',
                            'label' => 'Default social share image',
                            'value' => $s['og_image_gallery_id'],
                            'previewUrl' => $s['og_image_url'],
                            'kind' => 'image',
                        ])
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Recommended <strong>1200×630</strong> px JPG/WebP.</p>
                        <p class="qcat-field-error" data-error-for="og_image_gallery_id" hidden></p>
                    </div>
                </div>

                <div class="border-t border-slate-100 dark:border-slate-800 pt-6 space-y-4">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Default SEO</h2>
                    <div>
                        <label for="seo_default_title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Default title</label>
                        <input type="text" id="seo_default_title" name="seo_default_title" value="{{ $s['seo_default_title'] }}" class="panel-input mt-1 block w-full" placeholder="Examtube.in — Online Exams…">
                        <p class="qcat-field-error" data-error-for="seo_default_title" hidden></p>
                    </div>
                    <div>
                        <label for="seo_default_description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Default description</label>
                        <textarea id="seo_default_description" name="seo_default_description" rows="2" class="panel-input mt-1 block w-full">{{ $s['seo_default_description'] }}</textarea>
                        <p class="qcat-field-error" data-error-for="seo_default_description" hidden></p>
                    </div>
                    <div>
                        <label for="seo_default_keywords" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Default keywords</label>
                        <input type="text" id="seo_default_keywords" name="seo_default_keywords" value="{{ $s['seo_default_keywords'] }}" class="panel-input mt-1 block w-full" placeholder="online exams, mock tests…">
                        <p class="qcat-field-error" data-error-for="seo_default_keywords" hidden></p>
                    </div>
                </div>
            </div>

            {{-- Contact --}}
            <div x-show="tab === 'contact'" x-cloak class="px-4 py-5 sm:p-6 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                        <input type="email" id="email" name="email" value="{{ $s['email'] }}" class="panel-input mt-1 block w-full" placeholder="hello@examtube.in">
                        <p class="qcat-field-error" data-error-for="email" hidden></p>
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Phone</label>
                        <input type="text" id="phone" name="phone" value="{{ $s['phone'] }}" class="panel-input mt-1 block w-full" placeholder="+91 98765 43210">
                        <p class="qcat-field-error" data-error-for="phone" hidden></p>
                    </div>
                    <div>
                        <label for="whatsapp" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">WhatsApp</label>
                        <input type="text" id="whatsapp" name="whatsapp" value="{{ $s['whatsapp'] }}" class="panel-input mt-1 block w-full" placeholder="+91 98765 43210">
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Include country code. Shown as a WhatsApp link on the contact page.</p>
                        <p class="qcat-field-error" data-error-for="whatsapp" hidden></p>
                    </div>
                    <div>
                        <label for="hours" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Support hours</label>
                        <input type="text" id="hours" name="hours" value="{{ $s['hours'] }}" class="panel-input mt-1 block w-full" placeholder="Mon–Sat, 9:00 AM – 7:00 PM IST">
                        <p class="qcat-field-error" data-error-for="hours" hidden></p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="address" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Address</label>
                        <textarea id="address" name="address" rows="3" class="panel-input mt-1 block w-full" placeholder="Street, city, state, PIN">{{ $s['address'] }}</textarea>
                        <p class="qcat-field-error" data-error-for="address" hidden></p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="maps_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Google Maps URL</label>
                        <input type="url" id="maps_url" name="maps_url" value="{{ $s['maps_url'] }}" class="panel-input mt-1 block w-full" placeholder="https://maps.google.com/?q=…">
                        <p class="qcat-field-error" data-error-for="maps_url" hidden></p>
                    </div>
                </div>
            </div>

            {{-- Social --}}
            <div x-show="tab === 'social'" x-cloak class="px-4 py-5 sm:p-6 space-y-4">
                <p class="text-sm text-slate-500 dark:text-slate-400">Leave a URL empty to hide that platform. Visibility can also be toggled per network.</p>
                <div class="grid grid-cols-1 gap-4">
                    @foreach($platforms as $platform => $label)
                        @php $row = $social[$platform] ?? ['url' => '', 'is_visible' => false]; @endphp
                        <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-4 grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3 items-end">
                            <div>
                                <label for="social_{{ $platform }}_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ $label }}</label>
                                <input type="url" id="social_{{ $platform }}_url" name="social[{{ $platform }}][url]"
                                       value="{{ $row['url'] }}" class="panel-input mt-1 block w-full"
                                       placeholder="https://…">
                                <p class="qcat-field-error" data-error-for="social.{{ $platform }}.url" hidden></p>
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 pb-2">
                                <input type="checkbox" name="social[{{ $platform }}][is_visible]" value="1"
                                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                       {{ !empty($row['is_visible']) ? 'checked' : '' }}>
                                Visible
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Homepage --}}
            <div x-show="tab === 'homepage'" x-cloak class="px-4 py-5 sm:p-6 space-y-8">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Footer &amp; about</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="footer_about" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">About / footer text</label>
                            <textarea id="footer_about" name="footer_about" rows="3" class="panel-input mt-1 block w-full">{{ $s['footer_about'] }}</textarea>
                            <p class="qcat-field-error" data-error-for="footer_about" hidden></p>
                        </div>
                        <div>
                            <label for="footer_copyright" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Copyright</label>
                            <input type="text" id="footer_copyright" name="footer_copyright" value="{{ $s['footer_copyright'] }}" class="panel-input mt-1 block w-full" placeholder="© {year} Examtube.in">
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Use <code>{year}</code> for the current year.</p>
                            <p class="qcat-field-error" data-error-for="footer_copyright" hidden></p>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Homepage CTA</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="cta_title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">CTA title</label>
                            <input type="text" id="cta_title" name="cta_title" value="{{ $s['cta_title'] }}" class="panel-input mt-1 block w-full">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="cta_subtitle" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">CTA subtitle</label>
                            <textarea id="cta_subtitle" name="cta_subtitle" rows="2" class="panel-input mt-1 block w-full">{{ $s['cta_subtitle'] }}</textarea>
                        </div>
                        <div>
                            <label for="cta_primary_label" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Primary button label</label>
                            <input type="text" id="cta_primary_label" name="cta_primary_label" value="{{ $s['cta_primary_label'] }}" class="panel-input mt-1 block w-full">
                        </div>
                        <div>
                            <label for="cta_primary_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Primary button URL</label>
                            <input type="text" id="cta_primary_url" name="cta_primary_url" value="{{ $s['cta_primary_url'] }}" class="panel-input mt-1 block w-full" placeholder="/exams">
                        </div>
                        <div>
                            <label for="cta_secondary_label" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Secondary button label</label>
                            <input type="text" id="cta_secondary_label" name="cta_secondary_label" value="{{ $s['cta_secondary_label'] }}" class="panel-input mt-1 block w-full">
                        </div>
                        <div>
                            <label for="cta_secondary_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Secondary button URL</label>
                            <input type="text" id="cta_secondary_url" name="cta_secondary_url" value="{{ $s['cta_secondary_url'] }}" class="panel-input mt-1 block w-full" placeholder="/register">
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Newsletter block</h2>
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label for="newsletter_title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Title</label>
                            <input type="text" id="newsletter_title" name="newsletter_title" value="{{ $s['newsletter_title'] }}" class="panel-input mt-1 block w-full">
                        </div>
                        <div>
                            <label for="newsletter_subtitle" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Subtitle</label>
                            <textarea id="newsletter_subtitle" name="newsletter_subtitle" rows="2" class="panel-input mt-1 block w-full">{{ $s['newsletter_subtitle'] }}</textarea>
                        </div>
                        <div>
                            <label for="newsletter_cta" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Button label</label>
                            <input type="text" id="newsletter_cta" name="newsletter_cta" value="{{ $s['newsletter_cta'] }}" class="panel-input mt-1 block w-full">
                        </div>
                    </div>
                </div>
            </div>

            <div class="category-builder__footer px-4 py-4 sm:px-6 bg-slate-50 dark:bg-slate-900/50 flex justify-end gap-3 rounded-b-2xl">
                <button type="submit" class="panel-button-primary" id="org-settings-save-btn">Save organization settings</button>
            </div>
        </form>
    </x-page-card>

    {{-- Hero banners card (homepage tab companion, always below for space) --}}
    <div x-show="tab === 'homepage'" x-cloak>
        <x-page-card>
            <div class="px-4 py-5 sm:p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Hero banners</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Multiple slides with titles, CTAs, and desktop/mobile images.</p>
                    </div>
                    <button type="button" id="hero-add-btn" class="panel-button-primary">Add banner</button>
                </div>
                <div id="hero-list" class="space-y-3">
                    @forelse($heroes as $hero)
                        @php
                            $heroPayload = [
                                'id' => $hero->id,
                                'title' => $hero->title,
                                'subtitle' => $hero->subtitle,
                                'description' => $hero->description,
                                'badge_text' => $hero->badge_text,
                                'primary_cta_label' => $hero->primary_cta_label,
                                'primary_cta_url' => $hero->primary_cta_url,
                                'secondary_cta_label' => $hero->secondary_cta_label,
                                'secondary_cta_url' => $hero->secondary_cta_url,
                                'image_id' => $hero->image_id,
                                'mobile_image_id' => $hero->mobile_image_id,
                                'image_url' => $hero->image?->file_url,
                                'mobile_image_url' => $hero->mobileImage?->file_url,
                                'theme' => $hero->theme,
                                'show_search' => (bool) $hero->show_search,
                                'sort_order' => (int) $hero->sort_order,
                                'status' => $hero->status,
                                'starts_at' => optional($hero->starts_at)->format('Y-m-d\TH:i'),
                                'ends_at' => optional($hero->ends_at)->format('Y-m-d\TH:i'),
                            ];
                        @endphp
                        <div class="hero-row rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex flex-col sm:flex-row sm:items-center gap-3 justify-between"
                             data-hero-id="{{ $hero->id }}">
                            <div class="min-w-0 flex items-start gap-3">
                                @if($hero->image?->file_url)
                                    <img src="{{ $hero->image->file_url }}" alt="" class="h-14 w-20 rounded-lg object-cover shrink-0">
                                @else
                                    <div class="h-14 w-20 rounded-lg bg-slate-100 dark:bg-slate-800 shrink-0"></div>
                                @endif
                                <div class="min-w-0">
                                    <p class="font-medium text-slate-900 dark:text-white truncate">{{ $hero->title }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Order {{ $hero->sort_order }} · {{ ucfirst($hero->status) }}
                                        @if($hero->subtitle) · {{ \Illuminate\Support\Str::limit($hero->subtitle, 60) }} @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="panel-button-secondary text-sm hero-edit-btn" data-hero='@json($heroPayload)'>Edit</button>
                                <button type="button" class="panel-button-secondary text-sm text-red-600 hero-delete-btn" data-id="{{ $hero->id }}">Delete</button>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 dark:text-slate-400" id="hero-empty">No hero banners yet. Add your first slide.</p>
                    @endforelse
                </div>
            </div>
        </x-page-card>
    </div>
</div>

{{-- Hero modal --}}
<div id="hero-modal" class="fixed inset-0 z-[80] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-950/50" data-hero-modal-close></div>
    <div class="relative mx-auto mt-8 mb-8 w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-xl">
        <form id="hero-form" class="p-5 sm:p-6 space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 id="hero-modal-title" class="text-lg font-semibold text-slate-900 dark:text-white">Add hero banner</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Desktop and mobile images support crop via Gallery.</p>
                </div>
                <button type="button" class="panel-button-secondary text-sm" data-hero-modal-close>Close</button>
            </div>
            <input type="hidden" id="hero_id" name="id" value="">

            <div>
                <label for="hero_title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Title <span class="text-red-500">*</span></label>
                <input type="text" id="hero_title" name="title" required class="panel-input mt-1 block w-full" placeholder="Master every competitive exam">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="hero_subtitle" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Subtitle</label>
                    <input type="text" id="hero_subtitle" name="subtitle" class="panel-input mt-1 block w-full">
                </div>
                <div>
                    <label for="hero_badge_text" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Badge text</label>
                    <input type="text" id="hero_badge_text" name="badge_text" class="panel-input mt-1 block w-full">
                </div>
            </div>
            <div>
                <label for="hero_description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
                <textarea id="hero_description" name="description" rows="3" class="panel-input mt-1 block w-full"></textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="hero_primary_cta_label" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Primary CTA label</label>
                    <input type="text" id="hero_primary_cta_label" name="primary_cta_label" class="panel-input mt-1 block w-full">
                </div>
                <div>
                    <label for="hero_primary_cta_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Primary CTA URL</label>
                    <input type="text" id="hero_primary_cta_url" name="primary_cta_url" class="panel-input mt-1 block w-full" placeholder="/exams">
                </div>
                <div>
                    <label for="hero_secondary_cta_label" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Secondary CTA label</label>
                    <input type="text" id="hero_secondary_cta_label" name="secondary_cta_label" class="panel-input mt-1 block w-full">
                </div>
                <div>
                    <label for="hero_secondary_cta_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Secondary CTA URL</label>
                    <input type="text" id="hero_secondary_cta_url" name="secondary_cta_url" class="panel-input mt-1 block w-full">
                </div>
                <div>
                    <label for="hero_status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status <span class="text-red-500">*</span></label>
                    <select id="hero_status" name="status" class="panel-input mt-1 block w-full">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div>
                    <label for="hero_sort_order" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sort order</label>
                    <input type="number" id="hero_sort_order" name="sort_order" min="0" class="panel-input mt-1 block w-full" value="1">
                </div>
                <div>
                    <label for="hero_starts_at" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Starts at</label>
                    <input type="datetime-local" id="hero_starts_at" name="starts_at" class="panel-input mt-1 block w-full">
                </div>
                <div>
                    <label for="hero_ends_at" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ends at</label>
                    <input type="datetime-local" id="hero_ends_at" name="ends_at" class="panel-input mt-1 block w-full">
                </div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" id="hero_show_search" name="show_search" value="1" class="rounded border-slate-300 text-indigo-600" checked>
                Show search in hero
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6" id="hero-gallery-pickers">
                @include('backend.partials.gallery-picker', [
                    'name' => 'image_id',
                    'label' => 'Desktop image',
                    'value' => null,
                    'kind' => 'image',
                ])
                @include('backend.partials.gallery-picker', [
                    'name' => 'mobile_image_id',
                    'label' => 'Mobile image',
                    'value' => null,
                    'kind' => 'image',
                ])
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">Desktop recommended <strong>1920×800</strong>. Mobile recommended <strong>1080×1350</strong>.</p>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="panel-button-secondary" data-hero-modal-close>Cancel</button>
                <button type="submit" class="panel-button-primary" id="hero-save-btn">Save banner</button>
            </div>
        </form>
    </div>
</div>

@include('backend.partials.image-editor-modal')
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/backend/gallery-picker.css') }}?v={{ filemtime(public_path('css/backend/gallery-picker.css')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
    <link rel="stylesheet" href="{{ asset('css/backend/gallery.css') }}?v={{ filemtime(public_path('css/backend/gallery.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/backend/question-category-form.css') }}">
    <style>[x-cloak]{display:none!important}</style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
    <script src="{{ asset('js/backend/gallery-editor.js') }}?v={{ filemtime(public_path('js/backend/gallery-editor.js')) }}"></script>
    <script>
        window.galleryDataUrl = @json(route('admin.gallery.data'));
        window.galleryStoreUrl = @json(route('admin.gallery.store'));
        window.galleryCommitUrl = @json(route('admin.gallery.commit'));
        window.galleryCsrf = @json(csrf_token());
        window.contentFormConfig = {
            formId: 'organization-settings-form',
            module: 'settings',
            existingMedia: {},
            skipFormSubmitHook: true,
        };
        window.orgSettingsConfig = {
            updateUrl: @json(route('admin.settings.organization.update')),
            heroStoreUrl: @json(route('admin.settings.organization.heroes.store')),
            heroUpdateUrl: @json(url('/admin/settings/organization/heroes')),
            heroDeleteUrl: @json(url('/admin/settings/organization/heroes')),
            csrf: @json(csrf_token()),
            platforms: @json(array_keys($platforms)),
        };
    </script>
    <script src="{{ asset('js/backend/content-form-shared.js') }}?v={{ filemtime(public_path('js/backend/content-form-shared.js')) }}"></script>
    <script src="{{ asset('js/backend/settings-organization.js') }}?v={{ @filemtime(public_path('js/backend/settings-organization.js')) ?: time() }}"></script>
@endpush
