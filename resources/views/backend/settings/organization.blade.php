@extends('backend.layouts.app')

@section('title', 'Organization Settings')
@section('page-title', 'Organization Settings')
@section('content-container-class', 'max-w-none')

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

<div x-data="{ tab: (window.location.hash === '#faqs' ? 'faqs' : 'branding') }"
     x-effect="if (tab === 'faqs' && window.__emsLoadFaqs) window.__emsLoadFaqs()"
     class="space-y-6">
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
                    'faqs' => 'FAQs',
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

                <div class="org-branding-media-grid">
                    <div class="min-w-0">
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
                    <div class="min-w-0">
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
                    <div class="min-w-0">
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

            <div class="category-builder__footer px-4 py-4 sm:px-6 bg-slate-50 dark:bg-slate-900/50 flex justify-end gap-3 rounded-b-2xl"
                 x-show="tab !== 'faqs'" x-cloak>
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
                                'starts_at' => optional($hero->starts_at)->format('Y-m-d H:i'),
                                'ends_at' => optional($hero->ends_at)->format('Y-m-d H:i'),
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

    {{-- FAQs tab --}}
    <div x-show="tab === 'faqs'" x-cloak>
        <x-page-card>
            <div class="px-4 py-5 sm:p-6 space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Frequently asked questions</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Manage homepage FAQ content. Create and edit entries in a modal.
                        </p>
                    </div>
                    <button type="button" id="faq-add-btn"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add FAQ
                    </button>
                </div>

                <form id="faq-filters" class="faq-toolbar">
                    <div class="faq-toolbar__search">
                        <label for="faq_filter_search" class="faq-toolbar__label">Search</label>
                        <div class="faq-toolbar__search-wrap">
                            <svg class="faq-toolbar__search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/>
                            </svg>
                            <input type="search" id="faq_filter_search" name="search" class="faq-toolbar__input" placeholder="Search question or answer…">
                        </div>
                    </div>
                    <div class="faq-toolbar__field">
                        <label for="faq_filter_status" class="faq-toolbar__label">Status</label>
                        <select id="faq_filter_status" name="status" class="faq-toolbar__select">
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="faq-toolbar__field faq-toolbar__field--category">
                        <label for="faq_filter_category" class="faq-toolbar__label">Category</label>
                        <select id="faq_filter_category" name="category_id" class="faq-toolbar__select">
                            <option value="">All categories</option>
                            @foreach($faqCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="faq-toolbar__actions">
                        <button type="submit" class="faq-toolbar__btn faq-toolbar__btn--primary">Filter</button>
                        <button type="button" id="faq-filters-reset" class="faq-toolbar__btn">Reset</button>
                    </div>
                </form>

                <div class="faq-table-wrap">
                    <div class="overflow-x-auto">
                        <table class="faq-table">
                            <thead>
                                <tr>
                                    <th class="faq-table__col-question">Question</th>
                                    <th class="faq-table__col-category">Category</th>
                                    <th class="faq-table__col-status">Status</th>
                                    <th class="faq-table__col-order">Order</th>
                                    <th class="faq-table__col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="faq-table-body">
                                <tr>
                                    <td colspan="5" class="faq-table__empty">Loading FAQs…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="faq-pagination" class="faq-pagination"></div>
            </div>
        </x-page-card>
    </div>
</div>

@include('backend.settings.partials.org-modals')

@include('backend.partials.image-editor-modal')
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/backend/gallery-picker.css') }}?v={{ filemtime(public_path('css/backend/gallery-picker.css')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
    <link rel="stylesheet" href="{{ asset('css/backend/gallery.css') }}?v={{ filemtime(public_path('css/backend/gallery.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/backend/question-category-form.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/datetime-picker.css') }}?v={{ filemtime(public_path('css/components/datetime-picker.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/components/ems-dialog.css') }}?v={{ filemtime(public_path('css/components/ems-dialog.css')) }}">
    <style>
        [x-cloak]{display:none!important}
        .org-branding-media-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.75rem;
        }
        @media (min-width: 1024px) {
            .org-branding-media-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                column-gap: 2.25rem;
            }
        }
        .hero-gallery-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        @media (min-width: 640px) {
            .hero-gallery-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                column-gap: 1.75rem;
            }
        }

        /* FAQ toolbar */
        .faq-toolbar {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.85rem;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #f8fafc;
        }
        .dark .faq-toolbar {
            border-color: #334155;
            background: rgb(15 23 42 / 0.45);
        }
        @media (min-width: 1024px) {
            .faq-toolbar {
                grid-template-columns: minmax(0, 1.6fr) 10rem minmax(11rem, 1fr) auto;
                align-items: end;
                gap: 0.75rem 1rem;
            }
        }
        .faq-toolbar__label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #64748b;
        }
        .dark .faq-toolbar__label { color: #94a3b8; }
        .faq-toolbar__search-wrap { position: relative; }
        .faq-toolbar__search-icon {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            width: 1rem;
            height: 1rem;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }
        .faq-toolbar__input,
        .faq-toolbar__select {
            width: 100%;
            min-height: 2.6rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            background: #fff;
            color: #0f172a;
            font-size: 0.875rem;
            padding: 0.55rem 0.85rem;
        }
        .faq-toolbar__input { padding-left: 2.35rem; }
        .dark .faq-toolbar__input,
        .dark .faq-toolbar__select {
            border-color: #475569;
            background: #0f172a;
            color: #e2e8f0;
        }
        .faq-toolbar__input:focus,
        .faq-toolbar__select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgb(99 102 241 / 0.18);
        }
        .faq-toolbar__actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        @media (max-width: 1023px) {
            .faq-toolbar__actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }
            .faq-toolbar__btn { width: 100%; }
        }
        .faq-toolbar__btn {
            min-height: 2.6rem;
            padding: 0 1rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            background: #fff;
            color: #334155;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }
        .dark .faq-toolbar__btn {
            border-color: #475569;
            background: #1e293b;
            color: #e2e8f0;
        }
        .faq-toolbar__btn:hover { background: #f1f5f9; }
        .dark .faq-toolbar__btn:hover { background: #334155; }
        .faq-toolbar__btn--primary {
            border-color: #4f46e5;
            background: #4f46e5;
            color: #fff;
        }
        .faq-toolbar__btn--primary:hover {
            border-color: #4338ca;
            background: #4338ca;
            color: #fff;
        }

        /* FAQ table */
        .faq-table-wrap {
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #fff;
        }
        .dark .faq-table-wrap {
            border-color: #334155;
            background: rgb(2 6 23 / 0.35);
        }
        .faq-table {
            width: 100%;
            min-width: 44rem;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        .faq-table thead th {
            padding: 0.85rem 1rem;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        .dark .faq-table thead th {
            color: #94a3b8;
            background: rgb(15 23 42 / 0.85);
            border-bottom-color: #334155;
        }
        .faq-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.12s ease;
        }
        .dark .faq-table tbody tr { border-bottom-color: #1e293b; }
        .faq-table tbody tr:last-child { border-bottom: 0; }
        .faq-table tbody tr:hover { background: #f8fafc; }
        .dark .faq-table tbody tr:hover { background: rgb(30 41 59 / 0.45); }
        .faq-table td {
            padding: 0.95rem 1rem;
            vertical-align: middle;
            color: #475569;
        }
        .dark .faq-table td { color: #cbd5e1; }
        .faq-table__col-question { width: 42%; }
        .faq-table__col-category { width: 18%; }
        .faq-table__col-status { width: 12%; }
        .faq-table__col-order { width: 8%; }
        .faq-table__col-actions { width: 20%; text-align: right !important; }
        .faq-table__question {
            margin: 0;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.4;
        }
        .dark .faq-table__question { color: #f8fafc; }
        .faq-table__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-top: 0.45rem;
        }
        .faq-table__badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.15rem 0.55rem;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .faq-table__badge--featured {
            background: #fef3c7;
            color: #92400e;
        }
        .dark .faq-table__badge--featured {
            background: rgb(245 158 11 / 0.15);
            color: #fbbf24;
        }
        .faq-table__badge--active {
            background: #d1fae5;
            color: #065f46;
        }
        .dark .faq-table__badge--active {
            background: rgb(16 185 129 / 0.15);
            color: #34d399;
        }
        .faq-table__badge--inactive {
            background: #e2e8f0;
            color: #475569;
        }
        .dark .faq-table__badge--inactive {
            background: #1e293b;
            color: #94a3b8;
        }
        .faq-table__actions {
            display: inline-flex;
            justify-content: flex-end;
            gap: 0.45rem;
            width: 100%;
        }
        .faq-table__action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2rem;
            padding: 0 0.7rem;
            border-radius: 0.55rem;
            border: 1px solid #e2e8f0;
            background: #fff;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.12s ease, border-color 0.12s ease, color 0.12s ease;
        }
        .dark .faq-table__action {
            border-color: #334155;
            background: #0f172a;
        }
        .faq-table__action--edit { color: #4338ca; }
        .faq-table__action--edit:hover {
            border-color: #a5b4fc;
            background: #eef2ff;
        }
        .dark .faq-table__action--edit { color: #a5b4fc; }
        .dark .faq-table__action--edit:hover {
            border-color: rgb(99 102 241 / 0.45);
            background: rgb(99 102 241 / 0.12);
        }
        .faq-table__action--delete { color: #e11d48; }
        .faq-table__action--delete:hover {
            border-color: #fecdd3;
            background: #fff1f2;
        }
        .dark .faq-table__action--delete { color: #fb7185; }
        .dark .faq-table__action--delete:hover {
            border-color: rgb(244 63 94 / 0.35);
            background: rgb(244 63 94 / 0.12);
        }
        .faq-table__empty {
            padding: 2.75rem 1rem !important;
            text-align: center;
            color: #64748b;
        }
        .dark .faq-table__empty { color: #94a3b8; }
        .faq-table__badge--status { text-transform: capitalize; }
        .faq-table__order {
            display: inline-flex;
            min-width: 1.75rem;
            justify-content: center;
            font-variant-numeric: tabular-nums;
            font-weight: 600;
            color: #334155;
        }
        .dark .faq-table__order { color: #e2e8f0; }
        .faq-pagination {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            align-items: stretch;
            font-size: 0.8125rem;
            color: #64748b;
        }
        .dark .faq-pagination { color: #94a3b8; }
        @media (min-width: 640px) {
            .faq-pagination {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }
        .faq-pagination__meta strong {
            font-weight: 600;
            color: #334155;
        }
        .dark .faq-pagination__meta strong { color: #e2e8f0; }
        .faq-pagination__controls {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }
        .faq-pagination__btn {
            min-height: 2rem;
            padding: 0 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.55rem;
            background: #fff;
            color: #334155;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
        }
        .dark .faq-pagination__btn {
            border-color: #475569;
            background: #1e293b;
            color: #e2e8f0;
        }
        .faq-pagination__btn:hover:not(:disabled) { background: #f1f5f9; }
        .dark .faq-pagination__btn:hover:not(:disabled) { background: #334155; }
        .faq-pagination__btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .faq-pagination__page {
            display: inline-flex;
            min-width: 4.75rem;
            min-height: 2rem;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            border-radius: 0.55rem;
            padding: 0 0.55rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #475569;
        }
        .dark .faq-pagination__page {
            border-color: #334155;
            color: #cbd5e1;
        }
    </style>
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
        window.orgFaqConfig = {
            indexUrl: @json(route('admin.settings.organization.faqs.index')),
            storeUrl: @json(route('admin.settings.organization.faqs.store')),
            updateUrl: @json(url('/admin/settings/organization/faqs')),
            deleteUrl: @json(url('/admin/settings/organization/faqs')),
            csrf: @json(csrf_token()),
        };
    </script>
    <script src="{{ asset('js/components/datetime-picker.js') }}?v={{ filemtime(public_path('js/components/datetime-picker.js')) }}"></script>
    <script src="{{ asset('js/backend/content-form-shared.js') }}?v={{ filemtime(public_path('js/backend/content-form-shared.js')) }}"></script>
    <script src="{{ asset('js/backend/settings-organization.js') }}?v={{ @filemtime(public_path('js/backend/settings-organization.js')) ?: time() }}"></script>
    <script src="{{ asset('js/backend/settings-organization-faqs.js') }}?v={{ @filemtime(public_path('js/backend/settings-organization-faqs.js')) ?: time() }}"></script>
@endpush
