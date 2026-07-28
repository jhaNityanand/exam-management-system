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
    $applicationUrlDisplay = preg_replace('#^https?://#i', '', (string) ($s['application_url'] ?? ''));
@endphp

<div x-data="{ tab: (['faqs','social','contact','homepage','branding'].includes((window.location.hash || '').replace('#','')) ? (window.location.hash || '').replace('#','') : 'branding') }"
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
                            @click="tab = '{{ $id }}'; window.location.hash = '{{ $id }}'"
                            :class="tab === '{{ $id }}' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'"
                            class="rounded-lg border px-3 py-1.5 text-sm font-medium transition"
                            role="tab">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <form id="organization-settings-form" class="category-builder" novalidate>
            @csrf

            {{-- Branding --}}
            <div x-show="tab === 'branding'" x-cloak class="px-4 py-5 sm:p-6"
                 x-data="{
                    name: @js($s['site_name'] ?? ''),
                    logoText: @js($s['logo_text'] ?? ''),
                    tagline: @js($s['tagline'] ?? ''),
                    appUrl: @js($applicationUrlDisplay),
                    logoUrl: @js($s['logo_url'] ?? ''),
                    faviconUrl: @js($s['favicon_url'] ?? ''),
                    host() {
                        const raw = (this.appUrl || '').trim();
                        if (!raw) return 'examtube.in';
                        try {
                            const withScheme = /^https?:\/\//i.test(raw) ? raw : ('https://' + raw.replace(/^\/+/, ''));
                            return new URL(withScheme).host || 'examtube.in';
                        } catch (e) {
                            return raw.replace(/^https?:\/\//i, '').split('/')[0] || 'examtube.in';
                        }
                    }
                 }">
                <div class="org-brand-intro">
                    <h2 class="org-brand-intro__title">Brand identity</h2>
                    <p class="org-brand-intro__text">
                        Define how your organization appears across the public site, emails, and social previews.
                    </p>
                </div>

                <div class="org-brand-preview" aria-label="Brand preview">
                    <div class="org-brand-preview__media">
                        <template x-if="logoUrl">
                            <img :src="logoUrl" alt="" class="org-brand-preview__logo">
                        </template>
                        <template x-if="!logoUrl">
                            <div class="org-brand-preview__mark" x-text="(logoText || name || 'E').charAt(0).toUpperCase()"></div>
                        </template>
                        <template x-if="faviconUrl">
                            <img :src="faviconUrl" alt="" class="org-brand-preview__favicon" title="Favicon">
                        </template>
                    </div>
                    <div class="org-brand-preview__copy min-w-0">
                        <p class="org-brand-preview__name" x-text="name || 'Organization name'"></p>
                        <p class="org-brand-preview__tagline" x-text="tagline || 'Your tagline appears here'"></p>
                        <p class="org-brand-preview__url">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            <span x-text="host()"></span>
                        </p>
                    </div>
                </div>

                <section class="org-brand-section">
                    <div class="org-brand-section__head">
                        <h3 class="org-brand-section__title">Identity</h3>
                        <p class="org-brand-section__hint">Core name and messaging shown in the header, footer, and browser title.</p>
                    </div>
                    <div class="org-brand-grid">
                        <div class="org-brand-field org-brand-field--full">
                            <label for="site_name" class="org-brand-label">Organization name <span class="text-red-500">*</span></label>
                            <input type="text" id="site_name" name="site_name" required maxlength="120"
                                   x-model="name"
                                   value="{{ $s['site_name'] }}"
                                   class="panel-input org-brand-input"
                                   placeholder="Examtube.in">
                            <p class="qcat-field-error" data-error-for="site_name" hidden></p>
                        </div>
                        <div class="org-brand-field">
                            <label for="logo_text" class="org-brand-label">Logo text fallback</label>
                            <input type="text" id="logo_text" name="logo_text" maxlength="80"
                                   x-model="logoText"
                                   value="{{ $s['logo_text'] }}"
                                   class="panel-input org-brand-input"
                                   placeholder="Examtube">
                            <p class="org-brand-help">Shown when no logo image is selected.</p>
                            <p class="qcat-field-error" data-error-for="logo_text" hidden></p>
                        </div>
                        <div class="org-brand-field">
                            <label for="tagline" class="org-brand-label">Tagline</label>
                            <input type="text" id="tagline" name="tagline" maxlength="255"
                                   x-model="tagline"
                                   value="{{ $s['tagline'] }}"
                                   class="panel-input org-brand-input"
                                   placeholder="Practice smarter. Score higher.">
                            <p class="qcat-field-error" data-error-for="tagline" hidden></p>
                        </div>
                        <div class="org-brand-field org-brand-field--full">
                            <label for="application_url" class="org-brand-label">Application URL</label>
                            <div class="org-brand-url">
                                <span class="org-brand-url__prefix" aria-hidden="true">https://</span>
                                <input type="text" id="application_url" name="application_url" maxlength="255"
                                       x-model="appUrl"
                                       value="{{ $applicationUrlDisplay }}"
                                       class="panel-input org-brand-input org-brand-url__input"
                                       placeholder="examtube.in"
                                       inputmode="url"
                                       autocomplete="url">
                            </div>
                            <p class="org-brand-help">Public website address (domain or full URL). Used for branding, share links, and references like <strong>examtube.in</strong>.</p>
                            <p class="qcat-field-error" data-error-for="application_url" hidden></p>
                        </div>
                        <div class="org-brand-field org-brand-field--full">
                            <label for="description" class="org-brand-label">Description</label>
                            <textarea id="description" name="description" rows="3" maxlength="2000"
                                      class="panel-input org-brand-input"
                                      placeholder="Short organization description…">{{ $s['description'] }}</textarea>
                            <p class="qcat-field-error" data-error-for="description" hidden></p>
                        </div>
                    </div>
                </section>

                <section class="org-brand-section">
                    <div class="org-brand-section__head">
                        <h3 class="org-brand-section__title">Brand assets</h3>
                        <p class="org-brand-section__hint">Upload or pick images from the gallery. Recommended sizes keep headers and social cards crisp.</p>
                    </div>
                    <div class="org-branding-media-grid">
                        <div class="org-brand-asset min-w-0">
                            @include('backend.partials.gallery-picker', [
                                'name' => 'logo_gallery_id',
                                'label' => 'Logo',
                                'value' => $s['logo_gallery_id'],
                                'previewUrl' => $s['logo_url'],
                                'kind' => 'image',
                            ])
                            <p class="org-brand-help">Recommended <strong>400×120</strong> px transparent PNG/WebP.</p>
                            <p class="qcat-field-error" data-error-for="logo_gallery_id" hidden></p>
                        </div>
                        <div class="org-brand-asset min-w-0">
                            @include('backend.partials.gallery-picker', [
                                'name' => 'favicon_gallery_id',
                                'label' => 'Favicon',
                                'value' => $s['favicon_gallery_id'],
                                'previewUrl' => $s['favicon_url'],
                                'kind' => 'image',
                            ])
                            <p class="org-brand-help">Recommended <strong>512×512</strong> px square PNG.</p>
                            <p class="qcat-field-error" data-error-for="favicon_gallery_id" hidden></p>
                        </div>
                        <div class="org-brand-asset min-w-0">
                            @include('backend.partials.gallery-picker', [
                                'name' => 'og_image_gallery_id',
                                'label' => 'Default social share image',
                                'value' => $s['og_image_gallery_id'],
                                'previewUrl' => $s['og_image_url'],
                                'kind' => 'image',
                            ])
                            <p class="org-brand-help">Recommended <strong>1200×630</strong> px JPG/WebP for Open Graph / Twitter cards.</p>
                            <p class="qcat-field-error" data-error-for="og_image_gallery_id" hidden></p>
                        </div>
                    </div>
                </section>

                <section class="org-brand-section org-brand-section--last">
                    <div class="org-brand-section__head">
                        <h3 class="org-brand-section__title">Default SEO</h3>
                        <p class="org-brand-section__hint">Fallback metadata when a page does not define its own title or description.</p>
                    </div>
                    <div class="org-brand-grid">
                        <div class="org-brand-field org-brand-field--full">
                            <label for="seo_default_title" class="org-brand-label">Default title</label>
                            <input type="text" id="seo_default_title" name="seo_default_title" maxlength="160"
                                   value="{{ $s['seo_default_title'] }}"
                                   class="panel-input org-brand-input"
                                   placeholder="Examtube.in — Online Exams…">
                            <p class="qcat-field-error" data-error-for="seo_default_title" hidden></p>
                        </div>
                        <div class="org-brand-field org-brand-field--full">
                            <label for="seo_default_description" class="org-brand-label">Default description</label>
                            <textarea id="seo_default_description" name="seo_default_description" rows="2" maxlength="500"
                                      class="panel-input org-brand-input"
                                      placeholder="Prepare for competitive exams with curated mock tests…">{{ $s['seo_default_description'] }}</textarea>
                            <p class="qcat-field-error" data-error-for="seo_default_description" hidden></p>
                        </div>
                        <div class="org-brand-field org-brand-field--full">
                            <label for="seo_default_keywords" class="org-brand-label">Default keywords</label>
                            <input type="text" id="seo_default_keywords" name="seo_default_keywords" maxlength="500"
                                   value="{{ $s['seo_default_keywords'] }}"
                                   class="panel-input org-brand-input"
                                   placeholder="online exams, mock tests, competitive exams">
                            <p class="org-brand-help">Comma-separated. Optional for modern search engines, still useful for internal tooling.</p>
                            <p class="qcat-field-error" data-error-for="seo_default_keywords" hidden></p>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Contact --}}
            <div x-show="tab === 'contact'" x-cloak class="px-4 py-5 sm:p-6"
                 x-data="orgSupportHours({
                    rows: @js($s['support_hours'] ?? []),
                    days: @js($supportHourDays),
                    timezones: @js($supportHourTimezones),
                    min: 1,
                    max: 7
                 })"
                 x-init="init()">
                <div class="org-contact-intro">
                    <h2 class="org-contact-intro__title">Contact details</h2>
                    <p class="org-contact-intro__text">
                        These details appear on the public contact page and help candidates reach your team quickly.
                    </p>
                </div>

                <section class="org-contact-section">
                    <div class="org-contact-section__head">
                        <h3 class="org-contact-section__title">Reach us</h3>
                        <p class="org-contact-section__hint">Primary email, phone, and messaging channels.</p>
                    </div>
                    <div class="org-contact-grid">
                        <div class="org-contact-field">
                            <label for="email" class="org-contact-label">Email</label>
                            <input type="email" id="email" name="email" value="{{ $s['email'] }}" class="panel-input org-contact-input" placeholder="hello@examtube.in">
                            <p class="qcat-field-error" data-error-for="email" hidden></p>
                        </div>
                        <div class="org-contact-field">
                            <label for="phone" class="org-contact-label">Phone</label>
                            <input type="text" id="phone" name="phone" value="{{ $s['phone'] }}" class="panel-input org-contact-input" placeholder="+91 98765 43210">
                            <p class="qcat-field-error" data-error-for="phone" hidden></p>
                        </div>
                        <div class="org-contact-field">
                            <label for="whatsapp" class="org-contact-label">WhatsApp</label>
                            <input type="text" id="whatsapp" name="whatsapp" value="{{ $s['whatsapp'] }}" class="panel-input org-contact-input" placeholder="+91 98765 43210">
                            <p class="org-contact-help">Include country code. Shown as a WhatsApp link on the contact page.</p>
                            <p class="qcat-field-error" data-error-for="whatsapp" hidden></p>
                        </div>
                        <div class="org-contact-field org-contact-field--full">
                            <label for="address" class="org-contact-label">Address</label>
                            <textarea id="address" name="address" rows="3" class="panel-input org-contact-input" placeholder="Street, city, state, PIN">{{ $s['address'] }}</textarea>
                            <p class="qcat-field-error" data-error-for="address" hidden></p>
                        </div>
                        <div class="org-contact-field org-contact-field--full">
                            <label for="maps_url" class="org-contact-label">Google Maps URL</label>
                            <input type="url" id="maps_url" name="maps_url" value="{{ $s['maps_url'] }}" class="panel-input org-contact-input" placeholder="https://maps.google.com/?q=…">
                            <p class="qcat-field-error" data-error-for="maps_url" hidden></p>
                        </div>
                    </div>
                </section>

                <section class="org-contact-section org-contact-section--last">
                    <div class="org-contact-section__head org-hours-head">
                        <div>
                            <h3 class="org-contact-section__title">Support hours</h3>
                            <p class="org-contact-section__hint">Add 1–7 day schedules with from/to times and timezone. Default is Mon–Sat, 10:00 AM – 4:00 PM IST.</p>
                        </div>
                        <button type="button"
                                class="panel-button-secondary text-sm org-hours-add"
                                @click="addRow()"
                                :disabled="rows.length >= max"
                                :class="{ 'opacity-50 cursor-not-allowed': rows.length >= max }">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add day
                        </button>
                    </div>

                    <input type="hidden" name="hours" :value="summary()">

                    <div class="org-hours-list" id="org-support-hours">
                        <template x-for="(row, index) in rows" :key="row._key">
                            <div class="org-hours-row" :data-hours-index="index">
                                <div class="org-hours-row__fields">
                                    <div class="org-hours-field">
                                        <label class="org-hours-label" :for="'support_day_'+index">Day</label>
                                        <select class="panel-input org-contact-input"
                                                :id="'support_day_'+index"
                                                :name="'support_hours['+index+'][day]'"
                                                x-model="row.day">
                                            <template x-for="value in Object.keys(days)" :key="value">
                                                <option :value="value" x-text="days[value]"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="org-hours-field">
                                        <label class="org-hours-label" :for="'support_from_'+index">From</label>
                                        <div class="ems-dtp" data-ems-datetime data-mode="time">
                                            <div class="ems-dtp__control">
                                                <input type="text"
                                                       class="panel-input ems-dtp__input org-contact-input"
                                                       :id="'support_from_'+index"
                                                       :name="'support_hours['+index+'][from]'"
                                                       :value="row.from"
                                                       placeholder="10:00 AM"
                                                       autocomplete="off"
                                                       data-ems-datetime-input
                                                       data-enable-time="1"
                                                       data-no-calendar="1"
                                                       data-date-format="H:i"
                                                       data-alt-format="h:i K"
                                                       @change="row.from = $event.target.value"
                                                       @blur="row.from = $event.target.value">
                                                <button type="button" class="ems-dtp__icon" data-ems-datetime-toggle tabindex="-1" aria-label="Open time picker">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="org-hours-field">
                                        <label class="org-hours-label" :for="'support_to_'+index">To</label>
                                        <div class="ems-dtp" data-ems-datetime data-mode="time">
                                            <div class="ems-dtp__control">
                                                <input type="text"
                                                       class="panel-input ems-dtp__input org-contact-input"
                                                       :id="'support_to_'+index"
                                                       :name="'support_hours['+index+'][to]'"
                                                       :value="row.to"
                                                       placeholder="4:00 PM"
                                                       autocomplete="off"
                                                       data-ems-datetime-input
                                                       data-enable-time="1"
                                                       data-no-calendar="1"
                                                       data-date-format="H:i"
                                                       data-alt-format="h:i K"
                                                       @change="row.to = $event.target.value"
                                                       @blur="row.to = $event.target.value">
                                                <button type="button" class="ems-dtp__icon" data-ems-datetime-toggle tabindex="-1" aria-label="Open time picker">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="org-hours-field">
                                        <label class="org-hours-label" :for="'support_tz_'+index">Timezone</label>
                                        <select class="panel-input org-contact-input"
                                                :id="'support_tz_'+index"
                                                :name="'support_hours['+index+'][timezone]'"
                                                x-model="row.timezone">
                                            <template x-for="value in Object.keys(timezones)" :key="value">
                                                <option :value="value" x-text="timezones[value]"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                                <button type="button"
                                        class="org-hours-remove"
                                        @click="removeRow(index)"
                                        :disabled="rows.length <= min"
                                        :class="{ 'opacity-40 cursor-not-allowed': rows.length <= min }"
                                        title="Remove day"
                                        aria-label="Remove day">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                <div class="org-hours-row__errors">
                                    <p class="qcat-field-error org-hours-row__error" :data-error-for="'support_hours.'+index+'.day'" hidden></p>
                                    <p class="qcat-field-error org-hours-row__error" :data-error-for="'support_hours.'+index+'.from'" hidden></p>
                                    <p class="qcat-field-error org-hours-row__error" :data-error-for="'support_hours.'+index+'.to'" hidden></p>
                                    <p class="qcat-field-error org-hours-row__error" :data-error-for="'support_hours.'+index+'.timezone'" hidden></p>
                                </div>
                            </div>
                        </template>
                    </div>
                    <p class="org-contact-help mt-3" x-text="rows.length + ' of ' + max + ' day slots used · ' + summary()"></p>
                    <p class="qcat-field-error" data-error-for="support_hours" hidden></p>
                </section>
            </div>

            {{-- Social --}}
            <div x-show="tab === 'social'" x-cloak class="px-4 py-5 sm:p-6 space-y-5">
                <div class="org-social-intro">
                    <h2 class="org-social-intro__title">Social profiles</h2>
                    <p class="org-social-intro__text">
                        Add profile URLs for networks you want to show publicly. Leave a field empty to omit that platform.
                        Use the switch to show or hide a network without deleting its URL.
                    </p>
                </div>

                <div class="org-social-grid">
                    @foreach($platforms as $platform => $label)
                        @php
                            $row = $social[$platform] ?? ['url' => '', 'is_visible' => false];
                            $meta = $platformMeta[$platform] ?? [
                                'placeholder' => 'https://…',
                                'hint' => '',
                            ];
                        @endphp
                        <div class="org-social-field" data-social-platform="{{ $platform }}">
                            <div class="org-social-field__top">
                                <div class="org-social-field__brand">
                                    <span class="org-social-field__icon org-social-field__icon--{{ $platform }}" aria-hidden="true">
                                        @include('backend.partials.social-platform-icon', ['platform' => $platform, 'size' => 16])
                                    </span>
                                    <label for="social_{{ $platform }}_url" class="org-social-field__label">{{ $label }}</label>
                                </div>
                                <label class="org-social-switch" title="Show on website">
                                    <input type="checkbox"
                                           name="social[{{ $platform }}][is_visible]"
                                           value="1"
                                           class="org-social-switch__input"
                                           {{ !empty($row['is_visible']) ? 'checked' : '' }}>
                                    <span class="org-social-switch__track" aria-hidden="true"></span>
                                    <span class="org-social-switch__text">Visible</span>
                                </label>
                            </div>
                            <div class="org-social-field__control">
                                <input type="url"
                                       id="social_{{ $platform }}_url"
                                       name="social[{{ $platform }}][url]"
                                       value="{{ $row['url'] }}"
                                       class="panel-input org-social-field__input"
                                       placeholder="{{ $meta['placeholder'] }}"
                                       inputmode="url"
                                       autocomplete="url"
                                       maxlength="255"
                                       data-social-url="{{ $platform }}">
                            </div>
                            @if(!empty($meta['hint']))
                                <p class="org-social-field__hint">{{ $meta['hint'] }}</p>
                            @endif
                            <p class="qcat-field-error" data-error-for="social.{{ $platform }}.url" hidden></p>
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
            {{-- Enhanced FAQ header --}}
            <div class="faq-hero">
                <div class="faq-hero__inner">
                    <div class="faq-hero__icon-wrap" aria-hidden="true">
                        <svg class="faq-hero__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="faq-hero__copy">
                        <h2 class="faq-hero__title">Frequently Asked Questions</h2>
                        <p class="faq-hero__desc">Create and manage FAQ entries that appear on your public homepage. Use categories and featured flags to organize content.</p>
                    </div>
                </div>
                <button type="button" id="faq-add-btn" class="faq-hero__cta" aria-label="Add a new FAQ entry">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Add FAQ</span>
                </button>
            </div>

            {{-- Stats bar --}}
            <div class="faq-stats" id="faq-stats-bar" aria-label="FAQ stats">
                <div class="faq-stat faq-stat--total">
                    <span class="faq-stat__num" id="faq-stat-total">—</span>
                    <span class="faq-stat__label">Total</span>
                </div>
                <div class="faq-stat faq-stat--active">
                    <span class="faq-stat__dot faq-stat__dot--active"></span>
                    <span class="faq-stat__num" id="faq-stat-active">—</span>
                    <span class="faq-stat__label">Active</span>
                </div>
                <div class="faq-stat faq-stat--featured">
                    <span class="faq-stat__icon" aria-hidden="true">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z"/></svg>
                    </span>
                    <span class="faq-stat__num" id="faq-stat-featured">—</span>
                    <span class="faq-stat__label">Featured</span>
                </div>
                <div class="faq-stat faq-stat--cats">
                    <span class="faq-stat__num" id="faq-stat-cats">{{ count($faqCategories) }}</span>
                    <span class="faq-stat__label">Categories</span>
                </div>
            </div>

            <div class="px-4 pb-5 sm:px-6 space-y-5">
                {{-- Toolbar --}}
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
                        <button type="submit" class="faq-toolbar__btn faq-toolbar__btn--primary">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
                            Filter
                        </button>
                        <button type="button" id="faq-filters-reset" class="faq-toolbar__btn">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Reset
                        </button>
                    </div>
                </form>

                {{-- Table --}}
                <div class="faq-table-wrap">
                    <div class="overflow-x-auto">
                        <table class="faq-table" id="faq-main-table">
                            <thead>
                                <tr>
                                    <th class="faq-table__col-question">
                                        <span class="faq-th-inner">
                                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Question
                                        </span>
                                    </th>
                                    <th class="faq-table__col-category">
                                        <span class="faq-th-inner">
                                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                                            Category
                                        </span>
                                    </th>
                                    <th class="faq-table__col-status">Status</th>
                                    <th class="faq-table__col-order">#</th>
                                    <th class="faq-table__col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="faq-table-body">
                                <tr>
                                    <td colspan="5" class="faq-table__loading">
                                        <div class="faq-skeleton">
                                            <div class="faq-skeleton__row"><div class="faq-skeleton__bar faq-skeleton__bar--q"></div><div class="faq-skeleton__bar faq-skeleton__bar--c"></div><div class="faq-skeleton__bar faq-skeleton__bar--s"></div></div>
                                            <div class="faq-skeleton__row"><div class="faq-skeleton__bar faq-skeleton__bar--q"></div><div class="faq-skeleton__bar faq-skeleton__bar--c"></div><div class="faq-skeleton__bar faq-skeleton__bar--s"></div></div>
                                            <div class="faq-skeleton__row"><div class="faq-skeleton__bar faq-skeleton__bar--q"></div><div class="faq-skeleton__bar faq-skeleton__bar--c"></div><div class="faq-skeleton__bar faq-skeleton__bar--s"></div></div>
                                        </div>
                                    </td>
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

        /* Branding tab */
        .org-brand-intro { margin-bottom: 1.25rem; }
        .org-brand-intro__title,
        .org-brand-section__title {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
        }
        .dark .org-brand-intro__title,
        .dark .org-brand-section__title { color: #f8fafc; }
        .org-brand-intro__text,
        .org-brand-section__hint {
            margin: 0.35rem 0 0;
            font-size: 0.875rem;
            line-height: 1.5;
            color: #64748b;
        }
        .dark .org-brand-intro__text,
        .dark .org-brand-section__hint { color: #94a3b8; }
        .org-brand-preview {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem 1.1rem;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        }
        .dark .org-brand-preview {
            border-color: #334155;
            background: linear-gradient(135deg, rgb(15 23 42 / 0.9) 0%, rgb(49 46 129 / 0.25) 100%);
        }
        .org-brand-preview__media {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 4.5rem;
            height: 4.5rem;
            flex-shrink: 0;
        }
        .org-brand-preview__logo {
            max-width: 4.5rem;
            max-height: 2.75rem;
            object-fit: contain;
        }
        .org-brand-preview__mark {
            display: grid;
            place-items: center;
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 0.9rem;
            background: #4f46e5;
            color: #fff;
            font-size: 1.35rem;
            font-weight: 700;
        }
        .org-brand-preview__favicon {
            position: absolute;
            right: -0.15rem;
            bottom: 0.15rem;
            width: 1.35rem;
            height: 1.35rem;
            border-radius: 0.35rem;
            border: 2px solid #fff;
            background: #fff;
            object-fit: cover;
            box-shadow: 0 1px 3px rgb(15 23 42 / 0.2);
        }
        .dark .org-brand-preview__favicon { border-color: #0f172a; }
        .org-brand-preview__name {
            margin: 0;
            font-size: 1rem;
            font-weight: 650;
            color: #0f172a;
            line-height: 1.3;
        }
        .dark .org-brand-preview__name { color: #f8fafc; }
        .org-brand-preview__tagline {
            margin: 0.2rem 0 0;
            font-size: 0.8125rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dark .org-brand-preview__tagline { color: #94a3b8; }
        .org-brand-preview__url {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            margin: 0.45rem 0 0;
            font-size: 0.75rem;
            font-weight: 600;
            color: #4f46e5;
        }
        .dark .org-brand-preview__url { color: #a5b4fc; }
        .org-brand-section {
            padding: 1.35rem 0 1.5rem;
            border-top: 1px solid #f1f5f9;
        }
        .dark .org-brand-section { border-top-color: #1e293b; }
        .org-brand-section--last { padding-bottom: 0.25rem; }
        .org-brand-section__head { margin-bottom: 1rem; }
        .org-brand-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem 1.25rem;
        }
        @media (min-width: 640px) {
            .org-brand-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .org-brand-field--full { grid-column: 1 / -1; }
        }
        .org-brand-label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
        }
        .dark .org-brand-label { color: #e2e8f0; }
        .org-brand-input {
            width: 100%;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .org-brand-input:hover { border-color: #94a3b8; }
        .dark .org-brand-input:hover { border-color: #64748b; }
        .org-brand-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgb(99 102 241 / 0.18);
        }
        .org-brand-input.is-invalid {
            border-color: #f43f5e;
            box-shadow: 0 0 0 3px rgb(244 63 94 / 0.12);
        }
        .org-brand-help {
            margin: 0.4rem 0 0;
            font-size: 0.75rem;
            line-height: 1.45;
            color: #94a3b8;
        }
        .dark .org-brand-help { color: #64748b; }
        .org-brand-url {
            display: flex;
            align-items: stretch;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            overflow: hidden;
            background: #fff;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .dark .org-brand-url {
            border-color: #475569;
            background: #0f172a;
        }
        .org-brand-url:focus-within {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgb(99 102 241 / 0.18);
        }
        .org-brand-url__prefix {
            display: inline-flex;
            align-items: center;
            padding: 0 0.75rem;
            border-right: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
            font-size: 0.8125rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .dark .org-brand-url__prefix {
            border-right-color: #334155;
            background: #1e293b;
            color: #94a3b8;
        }
        .org-brand-url__input {
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            background: transparent !important;
        }
        .org-brand-url:has(.is-invalid) {
            border-color: #f43f5e;
            box-shadow: 0 0 0 3px rgb(244 63 94 / 0.12);
        }
        .org-brand-asset {
            padding: 0.15rem 0;
        }

        /* Contact tab */
        .org-contact-intro { margin-bottom: 1.25rem; }
        .org-contact-intro__title,
        .org-contact-section__title {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
        }
        .dark .org-contact-intro__title,
        .dark .org-contact-section__title { color: #f8fafc; }
        .org-contact-intro__text,
        .org-contact-section__hint {
            margin: 0.35rem 0 0;
            font-size: 0.875rem;
            line-height: 1.5;
            color: #64748b;
        }
        .dark .org-contact-intro__text,
        .dark .org-contact-section__hint { color: #94a3b8; }
        .org-contact-section {
            padding: 1.25rem 0 1.4rem;
            border-top: 1px solid #f1f5f9;
        }
        .dark .org-contact-section { border-top-color: #1e293b; }
        .org-contact-section--last { padding-bottom: 0.25rem; }
        .org-contact-section__head { margin-bottom: 1rem; }
        .org-contact-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem 1.25rem;
        }
        @media (min-width: 768px) {
            .org-contact-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .org-contact-field--full { grid-column: 1 / -1; }
        }
        .org-contact-label,
        .org-hours-label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #334155;
        }
        .dark .org-contact-label,
        .dark .org-hours-label { color: #e2e8f0; }
        .org-contact-input {
            width: 100%;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .org-contact-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgb(99 102 241 / 0.18);
        }
        .org-contact-help {
            margin: 0.4rem 0 0;
            font-size: 0.75rem;
            line-height: 1.45;
            color: #94a3b8;
        }
        .dark .org-contact-help { color: #64748b; }
        .org-hours-head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem 1rem;
        }
        .org-hours-add {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .org-hours-list {
            display: grid;
            gap: 0.85rem;
        }
        .org-hours-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.65rem;
            align-items: end;
            border-radius: 0.9rem;
            background: #f8fafc;
        }
        .dark .org-hours-row {
            border-color: #334155;
            background: rgb(15 23 42 / 0.45);
        }
        .org-hours-row__fields {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            min-width: 0;
        }
        @media (min-width: 768px) {
            .org-hours-row__fields {
                grid-template-columns: minmax(7.5rem, 1fr) minmax(7rem, 1fr) minmax(7rem, 1fr) minmax(10rem, 1.4fr);
            }
        }
        .org-hours-remove {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 0.65rem;
            border: 1px solid #fecdd3;
            background: #fff1f2;
            color: #e11d48;
            margin-bottom: 0.1rem;
        }
        .dark .org-hours-remove {
            border-color: rgb(244 63 94 / 0.35);
            background: rgb(244 63 94 / 0.12);
            color: #fb7185;
        }
        .org-hours-row__errors {
            grid-column: 1 / -1;
            display: grid;
            gap: 0.25rem;
        }
        .org-hours-row__error {
            margin: 0;
        }
        .org-hours-row .ems-dtp__control {
            position: relative;
        }
        .org-hours-row .ems-dtp__icon {
            position: absolute;
            right: 0.55rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            background: transparent;
            border: 0;
            padding: 0;
            display: inline-flex;
        }
        .org-social-intro__title {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
        }
        .dark .org-social-intro__title { color: #f8fafc; }
        .org-social-intro__text {
            margin: 0.35rem 0 0;
            font-size: 0.875rem;
            line-height: 1.5;
            color: #64748b;
        }
        .dark .org-social-intro__text { color: #94a3b8; }
        .org-social-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.15rem 1.5rem;
        }
        @media (min-width: 768px) {
            .org-social-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        .org-social-field {
            min-width: 0;
            padding: 0.15rem 0 0.35rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .dark .org-social-field { border-bottom-color: #1e293b; }
        @media (min-width: 768px) {
            .org-social-field {
                border-bottom: 0;
                padding: 0.25rem 0 0.5rem;
            }
        }
        .org-social-field__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.45rem;
        }
        .org-social-field__brand {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            min-width: 0;
        }
        .org-social-field__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.85rem;
            height: 1.85rem;
            border-radius: 0.55rem;
            flex-shrink: 0;
            color: #fff;
            background: #64748b;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .org-social-field:focus-within .org-social-field__icon,
        .org-social-field:hover .org-social-field__icon {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgb(15 23 42 / 0.12);
        }
        .org-social-field__icon--facebook { background: #1877f2; }
        .org-social-field__icon--instagram { background: linear-gradient(135deg, #f58529, #dd2a7b 50%, #8134af); }
        .org-social-field__icon--linkedin { background: #0a66c2; }
        .org-social-field__icon--x,
        .org-social-field__icon--twitter { background: #0f172a; }
        .dark .org-social-field__icon--x,
        .dark .org-social-field__icon--twitter { background: #e2e8f0; color: #0f172a; }
        .org-social-field__icon--youtube { background: #ff0000; }
        .org-social-field__icon--telegram { background: #229ed9; }
        .org-social-field__icon--whatsapp { background: #25d366; }
        .org-social-field__icon--discord { background: #5865f2; }
        .org-social-field__icon--github { background: #24292f; }
        .dark .org-social-field__icon--github { background: #e2e8f0; color: #0f172a; }
        .org-social-field__icon--pinterest { background: #e60023; }
        .org-social-field__icon--reddit { background: #ff4500; }
        .org-social-field__icon--threads { background: #000; }
        .dark .org-social-field__icon--threads { background: #e2e8f0; color: #0f172a; }
        .org-social-field__icon--tiktok { background: #010101; }
        .dark .org-social-field__icon--tiktok { background: #e2e8f0; color: #0f172a; }
        .org-social-field__icon--medium { background: #000; }
        .dark .org-social-field__icon--medium { background: #e2e8f0; color: #0f172a; }
        .org-social-field__icon--quora { background: #b92b27; }
        .org-social-field__icon--bluesky { background: #1185fe; }
        .org-social-field__label {
            margin: 0;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dark .org-social-field__label { color: #e2e8f0; }
        .org-social-field__input {
            width: 100%;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .org-social-field__input:hover {
            border-color: #94a3b8;
        }
        .dark .org-social-field__input:hover {
            border-color: #64748b;
        }
        .org-social-field__input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgb(99 102 241 / 0.18);
        }
        .org-social-field__input.is-invalid {
            border-color: #f43f5e;
            box-shadow: 0 0 0 3px rgb(244 63 94 / 0.12);
        }
        .org-social-field__hint {
            margin: 0.35rem 0 0;
            font-size: 0.75rem;
            line-height: 1.4;
            color: #94a3b8;
        }
        .dark .org-social-field__hint { color: #64748b; }
        .org-social-switch {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            cursor: pointer;
            user-select: none;
            flex-shrink: 0;
        }
        .org-social-switch__input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .org-social-switch__track {
            position: relative;
            width: 2.35rem;
            height: 1.3rem;
            border-radius: 999px;
            background: #cbd5e1;
            transition: background 0.15s ease;
            flex-shrink: 0;
        }
        .dark .org-social-switch__track { background: #475569; }
        .org-social-switch__track::after {
            content: '';
            position: absolute;
            top: 0.15rem;
            left: 0.15rem;
            width: 1rem;
            height: 1rem;
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 1px 2px rgb(15 23 42 / 0.2);
            transition: transform 0.15s ease;
        }
        .org-social-switch__input:checked + .org-social-switch__track {
            background: #4f46e5;
        }
        .org-social-switch__input:checked + .org-social-switch__track::after {
            transform: translateX(1.05rem);
        }
        .org-social-switch__input:focus-visible + .org-social-switch__track {
            box-shadow: 0 0 0 3px rgb(99 102 241 / 0.28);
        }
        .org-social-switch__text {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
        }
        .dark .org-social-switch__text { color: #94a3b8; }
        .org-social-switch__input:checked ~ .org-social-switch__text {
            color: #4f46e5;
        }
        .dark .org-social-switch__input:checked ~ .org-social-switch__text {
            color: #a5b4fc;
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


        /* ──────────────────────────────────────
           FAQ hero header
        ────────────────────────────────────── */
        .faq-hero {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem 1.5rem;
            padding: 1.5rem 1.5rem 1.25rem;
            background: linear-gradient(135deg, #f8faff 0%, #eef2ff 100%);
            border-bottom: 1px solid #e0e7ff;
        }
        .dark .faq-hero {
            background: linear-gradient(135deg, rgb(15 23 42 / 0.9) 0%, rgb(49 46 129 / 0.22) 100%);
            border-bottom-color: rgb(99 102 241 / 0.18);
        }
        .faq-hero__inner {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 0;
        }
        .faq-hero__icon-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border-radius: 0.9rem;
            background: #4f46e5;
            box-shadow: 0 4px 14px rgb(79 70 229 / 0.32);
            flex-shrink: 0;
        }
        .faq-hero__icon {
            width: 1.5rem;
            height: 1.5rem;
            color: #fff;
        }
        .faq-hero__title {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e1b4b;
            letter-spacing: -0.01em;
        }
        .dark .faq-hero__title { color: #e0e7ff; }
        .faq-hero__desc {
            margin: 0.3rem 0 0;
            font-size: 0.8125rem;
            line-height: 1.55;
            color: #6366f1;
        }
        .dark .faq-hero__desc { color: #a5b4fc; }
        .faq-hero__cta {
            display: inline-flex;
            flex-shrink: 0;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.15rem;
            border-radius: 0.85rem;
            background: #4f46e5;
            color: #fff;
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgb(79 70 229 / 0.35);
            transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.12s ease;
        }
        .faq-hero__cta:hover {
            background: #4338ca;
            box-shadow: 0 6px 18px rgb(79 70 229 / 0.42);
            transform: translateY(-1px);
        }
        .faq-hero__cta:active { transform: translateY(0); }

        /* ──────────────────────────────────────
           FAQ stats bar
        ────────────────────────────────────── */
        .faq-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .dark .faq-stats { border-bottom-color: #1e293b; }
        .faq-stat {
            flex: 1 1 auto;
            display: flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.85rem 1.25rem;
            border-right: 1px solid #f1f5f9;
            min-width: 6rem;
        }
        .dark .faq-stat { border-right-color: #1e293b; }
        .faq-stat:last-child { border-right: 0; }
        .faq-stat__num {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e293b;
            font-variant-numeric: tabular-nums;
            line-height: 1;
        }
        .dark .faq-stat__num { color: #f1f5f9; }
        .faq-stat__label {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #94a3b8;
        }
        .dark .faq-stat__label { color: #64748b; }
        .faq-stat__dot {
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .faq-stat__dot--active { background: #10b981; box-shadow: 0 0 0 3px rgb(16 185 129 / 0.18); }
        .faq-stat__icon {
            display: inline-flex;
            color: #f59e0b;
        }
        .faq-stat--total .faq-stat__num { color: #4f46e5; }
        .dark .faq-stat--total .faq-stat__num { color: #a5b4fc; }
        .faq-stat--active .faq-stat__num { color: #059669; }
        .dark .faq-stat--active .faq-stat__num { color: #34d399; }
        .faq-stat--featured .faq-stat__num { color: #d97706; }
        .dark .faq-stat--featured .faq-stat__num { color: #fbbf24; }

        /* ──────────────────────────────────────
           FAQ toolbar
        ────────────────────────────────────── */
        .faq-toolbar {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.85rem;
            padding: 1rem;
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
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

        /* ──────────────────────────────────────
           FAQ table
        ────────────────────────────────────── */
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
            font-size: 0.68rem;
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
        .faq-th-inner {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
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
            gap: 0.25rem;
            border-radius: 999px;
            padding: 0.15rem 0.6rem;
            font-size: 0.67rem;
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
        .faq-table__badge--status { text-transform: capitalize; }
        .faq-table__category-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.25rem 0.65rem;
            border-radius: 0.5rem;
            background: #eef2ff;
            color: #4338ca;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .dark .faq-table__category-pill {
            background: rgb(99 102 241 / 0.15);
            color: #a5b4fc;
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
            gap: 0.3rem;
            min-height: 2rem;
            padding: 0 0.75rem;
            border-radius: 0.6rem;
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
            padding: 3.5rem 1rem !important;
            text-align: center;
        }
        .faq-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
        }
        .faq-empty-state__icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 1rem;
            background: #f1f5f9;
            color: #94a3b8;
        }
        .dark .faq-empty-state__icon { background: #1e293b; }
        .faq-empty-state__title {
            font-size: 0.9375rem;
            font-weight: 600;
            color: #334155;
            margin: 0;
        }
        .dark .faq-empty-state__title { color: #e2e8f0; }
        .faq-empty-state__desc {
            font-size: 0.8125rem;
            color: #94a3b8;
            margin: 0;
        }
        .dark .faq-table__empty { color: #94a3b8; }
        .faq-table__badge--status { text-transform: capitalize; }
        .faq-table__order {
            display: inline-flex;
            min-width: 1.75rem;
            align-items: center;
            justify-content: center;
            height: 1.75rem;
            border-radius: 0.45rem;
            background: #f1f5f9;
            font-variant-numeric: tabular-nums;
            font-weight: 700;
            font-size: 0.8125rem;
            color: #334155;
        }
        .dark .faq-table__order {
            background: #1e293b;
            color: #e2e8f0;
        }

        /* ──────────────────────────────────────
           FAQ skeleton loader
        ────────────────────────────────────── */
        .faq-table__loading { padding: 0 !important; }
        .faq-skeleton {
            padding: 0.5rem 0;
        }
        .faq-skeleton__row {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1rem 1rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .dark .faq-skeleton__row { border-bottom-color: #1e293b; }
        .faq-skeleton__row:last-child { border-bottom: 0; }
        .faq-skeleton__bar {
            height: 0.75rem;
            border-radius: 999px;
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: faq-shimmer 1.4s infinite linear;
        }
        .dark .faq-skeleton__bar {
            background: linear-gradient(90deg, #1e293b 25%, #334155 50%, #1e293b 75%);
            background-size: 200% 100%;
        }
        .faq-skeleton__bar--q { flex: 2; }
        .faq-skeleton__bar--c { flex: 0 0 7rem; }
        .faq-skeleton__bar--s { flex: 0 0 4rem; }
        @keyframes faq-shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* ──────────────────────────────────────
           FAQ pagination
        ────────────────────────────────────── */
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

        window.orgSupportHours = function orgSupportHours(config) {
            const dayOrder = Object.keys(config.days || {});
            let keySeed = 1;

            const withKeys = (rows) => (Array.isArray(rows) ? rows : []).map((row) => ({
                _key: 'sh-' + (keySeed++),
                day: row.day || 'monday',
                from: row.from || '10:00',
                to: row.to || '16:00',
                timezone: row.timezone || 'Asia/Kolkata',
            }));

            return {
                rows: withKeys(config.rows),
                days: config.days || {},
                timezones: config.timezones || {},
                min: config.min || 1,
                max: config.max || 7,
                init() {
                    if (!this.rows.length) {
                        this.rows = withKeys(
                            ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'].map((day) => ({
                                day,
                                from: '10:00',
                                to: '16:00',
                                timezone: 'Asia/Kolkata',
                            }))
                        );
                    }
                    this.$nextTick(() => this.mountPickers());
                },
                unusedDay() {
                    const used = new Set(this.rows.map((row) => row.day));
                    return dayOrder.find((day) => !used.has(day)) || dayOrder[0] || 'monday';
                },
                addRow() {
                    if (this.rows.length >= this.max) return;
                    this.rows.push({
                        _key: 'sh-' + (keySeed++),
                        day: this.unusedDay(),
                        from: '10:00',
                        to: '16:00',
                        timezone: this.rows[0]?.timezone || 'Asia/Kolkata',
                    });
                    this.$nextTick(() => this.mountPickers());
                },
                removeRow(index) {
                    if (this.rows.length <= this.min) return;
                    const rowEl = this.$el.querySelector(`[data-hours-index="${index}"]`);
                    rowEl?.querySelectorAll('[data-ems-datetime-input]').forEach((input) => {
                        if (input._flatpickr) input._flatpickr.destroy();
                    });
                    this.rows.splice(index, 1);
                    this.$nextTick(() => this.mountPickers());
                },
                mountPickers() {
                    const root = this.$el.querySelector('#org-support-hours') || this.$el;
                    window.EmsDateTimePicker?.initAll?.(root)?.then?.(() => {
                        root.querySelectorAll('[data-ems-datetime-input]').forEach((input) => {
                            if (input._orgHoursBound) return;
                            input._orgHoursBound = true;
                            const sync = () => {
                                const match = String(input.name || '').match(/support_hours\[(\d+)\]\[(from|to)\]/);
                                if (!match) return;
                                const idx = Number(match[1]);
                                const field = match[2];
                                if (this.rows[idx]) {
                                    this.rows[idx][field] = input.value;
                                }
                            };
                            input.addEventListener('change', sync);
                            input.addEventListener('blur', sync);
                        });
                    });
                },
                formatAmPm(value) {
                    const raw = String(value || '').trim();
                    const withMeridiem = raw.match(/^(\d{1,2}):(\d{2})\s*([AaPp][Mm])$/);
                    if (withMeridiem) {
                        let hour = Number(withMeridiem[1]);
                        const minute = withMeridiem[2];
                        const meridiem = withMeridiem[3].toUpperCase();
                        return `${hour}:${minute} ${meridiem}`;
                    }
                    const match = raw.match(/^(\d{1,2}):(\d{2})$/);
                    if (!match) return value || '';
                    let hour = Number(match[1]);
                    const minute = match[2];
                    const meridiem = hour >= 12 ? 'PM' : 'AM';
                    hour = hour % 12;
                    if (hour === 0) hour = 12;
                    return `${hour}:${minute} ${meridiem}`;
                },
                summary() {
                    return this.rows.map((row) => {
                        const day = this.days[row.day] || row.day;
                        const tz = row.timezone === 'Asia/Kolkata' ? 'IST' : row.timezone;
                        return `${day} ${this.formatAmPm(row.from)} – ${this.formatAmPm(row.to)} (${tz})`;
                    }).join('; ');
                },
            };
        };
    </script>
    <script src="{{ asset('js/components/datetime-picker.js') }}?v={{ filemtime(public_path('js/components/datetime-picker.js')) }}"></script>
    <script src="{{ asset('js/backend/content-form-shared.js') }}?v={{ filemtime(public_path('js/backend/content-form-shared.js')) }}"></script>
    <script src="{{ asset('js/backend/settings-organization.js') }}?v={{ @filemtime(public_path('js/backend/settings-organization.js')) ?: time() }}"></script>
    <script src="{{ asset('js/backend/settings-organization-faqs.js') }}?v={{ @filemtime(public_path('js/backend/settings-organization-faqs.js')) ?: time() }}"></script>
@endpush
