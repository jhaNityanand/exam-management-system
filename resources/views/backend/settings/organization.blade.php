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

<div x-data="{ tab: (['members','faqs','social','contact','homepage','branding'].includes((window.location.hash || '').replace('#','')) ? (window.location.hash || '').replace('#','') : 'branding') }"
     x-effect="if (tab === 'faqs' && window.__emsLoadFaqs) window.__emsLoadFaqs(); if (tab === 'members' && window.__emsLoadMembers) window.__emsLoadMembers()"
     class="space-y-6">
    <x-page-card>
        <div class="px-4 py-5 sm:px-6 border-b border-slate-100 dark:border-slate-800">
            <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Organization Settings</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Control branding, contact details, social profiles, homepage content, and organization members.
            </p>
            <div class="mt-4 flex flex-wrap gap-2" role="tablist" aria-label="Organization settings sections">
                @foreach([
                    'branding' => 'Branding',
                    'contact' => 'Contact',
                    'social' => 'Social media',
                    'homepage' => 'Homepage',
                    'faqs' => 'FAQs',
                    'members' => 'Members',
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
                            <div class="org-brand-preview__mark" aria-hidden="true">
                                <img src="{{ asset('images/brand/admin-mark.svg') }}" alt="" width="40" height="40" class="org-brand-preview__mark-img">
                            </div>
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
                                   placeholder="Learn, practice, and stay informed — in one place.">
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
                                      placeholder="Platform for online exams, blogs, news, articles, organizations, and learning content.">{{ $s['description'] }}</textarea>
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
                                   placeholder="Examtube.in — Exams, Blogs, News, Articles & Learning">
                            <p class="qcat-field-error" data-error-for="seo_default_title" hidden></p>
                        </div>
                        <div class="org-brand-field org-brand-field--full">
                            <label for="seo_default_description" class="org-brand-label">Default description</label>
                            <textarea id="seo_default_description" name="seo_default_description" rows="2" maxlength="500"
                                      class="panel-input org-brand-input"
                                      placeholder="Practice online exams, read blogs and articles, follow education news…">{{ $s['seo_default_description'] }}</textarea>
                            <p class="qcat-field-error" data-error-for="seo_default_description" hidden></p>
                        </div>
                        <div class="org-brand-field org-brand-field--full">
                            <label for="seo_default_keywords" class="org-brand-label">Default keywords</label>
                            <input type="text" id="seo_default_keywords" name="seo_default_keywords" maxlength="500"
                                   value="{{ $s['seo_default_keywords'] }}"
                                   class="panel-input org-brand-input"
                                   placeholder="online exams, blogs, news, articles, organizations, learning">
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
                        These details appear on the public contact page and help people reach your team quickly.
                    </p>
                </div>

                <section class="org-contact-section">
                    <div class="org-contact-section__head">
                        <h3 class="org-contact-section__title">Reach us</h3>
                        <p class="org-contact-section__hint">Primary email, phone, messaging, and location.</p>
                    </div>
                    <div class="org-contact-grid">
                        <div class="org-contact-field">
                            <label for="email" class="org-contact-label">Email</label>
                            <input type="email" id="email" name="email" value="{{ $s['email'] }}" class="panel-input org-contact-input" placeholder="hello@examtube.in" autocomplete="email">
                            <p class="qcat-field-error" data-error-for="email" hidden></p>
                        </div>
                        <div class="org-contact-field">
                            <label for="phone" class="org-contact-label">Phone</label>
                            <input type="text" id="phone" name="phone" value="{{ $s['phone'] }}" class="panel-input org-contact-input" placeholder="+91 98765 43210" autocomplete="tel">
                            <p class="qcat-field-error" data-error-for="phone" hidden></p>
                        </div>
                        <div class="org-contact-field">
                            <label for="whatsapp" class="org-contact-label">WhatsApp</label>
                            <input type="text" id="whatsapp" name="whatsapp" value="{{ $s['whatsapp'] }}" class="panel-input org-contact-input" placeholder="+91 98765 43210" autocomplete="tel">
                            <p class="org-contact-help">Include country code. Shown as a WhatsApp link on the contact page.</p>
                            <p class="qcat-field-error" data-error-for="whatsapp" hidden></p>
                        </div>
                        <div class="org-contact-field org-contact-field--full">
                            <label for="address" class="org-contact-label">Address</label>
                            <textarea id="address" name="address" rows="3" class="panel-input org-contact-input" placeholder="Lion Gate, Fort, Mumbai, Maharashtra 400001">{{ $s['address'] }}</textarea>
                            <p class="org-contact-help">Used on the contact page and in structured data where available.</p>
                            <p class="qcat-field-error" data-error-for="address" hidden></p>
                        </div>
                        <div class="org-contact-field org-contact-field--full">
                            <label for="maps_url" class="org-contact-label">Google Maps URL</label>
                            <input type="url" id="maps_url" name="maps_url" value="{{ $s['maps_url'] }}" class="panel-input org-contact-input" placeholder="https://maps.google.com/?q=Lion+Gate,+Fort,+Mumbai">
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
                <div class="org-home-intro">
                    <h2 class="org-home-intro__title">Homepage content</h2>
                    <p class="org-home-intro__text">
                        Footer copy, call-to-action, and newsletter messaging shown across the public site.
                    </p>
                </div>

                <section class="org-home-section">
                    <div class="org-home-section__head">
                        <h3 class="org-home-section__title">Footer &amp; about</h3>
                        <p class="org-home-section__hint">Short about text and copyright line in the site footer.</p>
                    </div>
                    <div class="org-home-grid">
                        <div class="org-home-field org-home-field--full">
                            <label for="footer_about" class="org-home-label">About / footer text</label>
                            <textarea id="footer_about" name="footer_about" rows="3" class="panel-input org-home-input">{{ $s['footer_about'] }}</textarea>
                            <p class="qcat-field-error" data-error-for="footer_about" hidden></p>
                        </div>
                        <div class="org-home-field org-home-field--full">
                            <label for="footer_copyright" class="org-home-label">Copyright</label>
                            <input type="text" id="footer_copyright" name="footer_copyright" value="{{ $s['footer_copyright'] }}" class="panel-input org-home-input" placeholder="© {year} Examtube.in">
                            <p class="org-home-help">Use <code>{year}</code> for the current year.</p>
                            <p class="qcat-field-error" data-error-for="footer_copyright" hidden></p>
                        </div>
                    </div>
                </section>

                <section class="org-home-section">
                    <div class="org-home-section__head">
                        <h3 class="org-home-section__title">Homepage CTA</h3>
                        <p class="org-home-section__hint">Primary call-to-action block promoting exams, blogs, news, and learning content.</p>
                    </div>
                    <div class="org-home-grid org-home-grid--2">
                        <div class="org-home-field org-home-field--full">
                            <label for="cta_title" class="org-home-label">CTA title</label>
                            <input type="text" id="cta_title" name="cta_title" value="{{ $s['cta_title'] }}" class="panel-input org-home-input">
                        </div>
                        <div class="org-home-field org-home-field--full">
                            <label for="cta_subtitle" class="org-home-label">CTA subtitle</label>
                            <textarea id="cta_subtitle" name="cta_subtitle" rows="2" class="panel-input org-home-input">{{ $s['cta_subtitle'] }}</textarea>
                        </div>
                        <div class="org-home-field">
                            <label for="cta_primary_label" class="org-home-label">Primary button label</label>
                            <input type="text" id="cta_primary_label" name="cta_primary_label" value="{{ $s['cta_primary_label'] }}" class="panel-input org-home-input">
                        </div>
                        <div class="org-home-field">
                            <label for="cta_primary_url" class="org-home-label">Primary button URL</label>
                            <input type="text" id="cta_primary_url" name="cta_primary_url" value="{{ $s['cta_primary_url'] }}" class="panel-input org-home-input" placeholder="/exams">
                        </div>
                        <div class="org-home-field">
                            <label for="cta_secondary_label" class="org-home-label">Secondary button label</label>
                            <input type="text" id="cta_secondary_label" name="cta_secondary_label" value="{{ $s['cta_secondary_label'] }}" class="panel-input org-home-input">
                        </div>
                        <div class="org-home-field">
                            <label for="cta_secondary_url" class="org-home-label">Secondary button URL</label>
                            <input type="text" id="cta_secondary_url" name="cta_secondary_url" value="{{ $s['cta_secondary_url'] }}" class="panel-input org-home-input" placeholder="/blogs">
                        </div>
                    </div>
                </section>

                <section class="org-home-section org-home-section--last">
                    <div class="org-home-section__head">
                        <h3 class="org-home-section__title">Newsletter block</h3>
                        <p class="org-home-section__hint">Signup prompt for weekly learning and exam updates.</p>
                    </div>
                    <div class="org-home-grid">
                        <div class="org-home-field org-home-field--full">
                            <label for="newsletter_title" class="org-home-label">Title</label>
                            <input type="text" id="newsletter_title" name="newsletter_title" value="{{ $s['newsletter_title'] }}" class="panel-input org-home-input">
                        </div>
                        <div class="org-home-field org-home-field--full">
                            <label for="newsletter_subtitle" class="org-home-label">Subtitle</label>
                            <textarea id="newsletter_subtitle" name="newsletter_subtitle" rows="2" class="panel-input org-home-input">{{ $s['newsletter_subtitle'] }}</textarea>
                        </div>
                        <div class="org-home-field">
                            <label for="newsletter_cta" class="org-home-label">Button label</label>
                            <input type="text" id="newsletter_cta" name="newsletter_cta" value="{{ $s['newsletter_cta'] }}" class="panel-input org-home-input">
                        </div>
                    </div>
                </section>
            </div>

            <div class="category-builder__footer px-4 py-4 sm:px-6 bg-slate-50 dark:bg-slate-900/50 flex justify-end gap-3 rounded-b-2xl"
                 x-show="tab !== 'faqs' && tab !== 'members'" x-cloak>
                <button type="submit" class="panel-button-primary" id="org-settings-save-btn">Save organization settings</button>
            </div>
        </form>
    </x-page-card>

    {{-- Hero banners card (edit existing slides only) --}}
    <div x-show="tab === 'homepage'" x-cloak>
        <x-page-card>
            <div class="px-4 py-5 sm:p-6 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Hero banners</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Edit the existing homepage slides. Adding or deleting banners is disabled so the frontend always shows the seeded set of cards.
                    </p>
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
                                <button type="button" class="panel-button-secondary text-sm hero-edit-btn" data-hero="{{ e(json_encode($heroPayload, JSON_UNESCAPED_UNICODE)) }}">Edit</button>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 dark:text-slate-400" id="hero-empty">No hero banners are available to edit yet.</p>
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
                        <select id="faq_filter_status" name="status" class="faq-toolbar__select" data-placeholder="Select status">
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="faq-toolbar__field faq-toolbar__field--category">
                        <label for="faq_filter_category" class="faq-toolbar__label">Category</label>
                        <select id="faq_filter_category" name="category_id" class="faq-toolbar__select" data-placeholder="Select category">
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

    {{-- Members tab --}}
    <div x-show="tab === 'members'" x-cloak>
        <x-page-card>
            <div class="faq-hero">
                <div class="faq-hero__inner">
                    <div class="faq-hero__icon-wrap" aria-hidden="true">
                        <svg class="faq-hero__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div class="faq-hero__copy">
                        <h2 class="faq-hero__title">Organization members</h2>
                        <p class="faq-hero__desc">Invite and manage organization admins. New members are assigned the <strong>org_admin</strong> role automatically.</p>
                    </div>
                </div>
                <button type="button" id="member-add-btn" class="faq-hero__cta" aria-label="Add a new organization member">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Add member</span>
                </button>
            </div>

            <div class="faq-stats" id="member-stats-bar" aria-label="Member stats">
                <div class="faq-stat faq-stat--total">
                    <span class="faq-stat__num" id="member-stat-total">—</span>
                    <span class="faq-stat__label">Total</span>
                </div>
                <div class="faq-stat faq-stat--active">
                    <span class="faq-stat__dot faq-stat__dot--active"></span>
                    <span class="faq-stat__num" id="member-stat-active">—</span>
                    <span class="faq-stat__label">Active</span>
                </div>
                <div class="faq-stat faq-stat--featured">
                    <span class="faq-stat__num" id="member-stat-inactive">—</span>
                    <span class="faq-stat__label">Inactive</span>
                </div>
            </div>

            <div class="px-4 pb-5 sm:px-6 space-y-5">
                <form id="member-filters" class="faq-toolbar">
                    <div class="faq-toolbar__search">
                        <label for="member_filter_search" class="faq-toolbar__label">Search</label>
                        <div class="faq-toolbar__search-wrap">
                            <svg class="faq-toolbar__search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/>
                            </svg>
                            <input type="search" id="member_filter_search" name="search" class="faq-toolbar__input" placeholder="Search name or email…">
                        </div>
                    </div>
                    <div class="faq-toolbar__field">
                        <label for="member_filter_status" class="faq-toolbar__label">Status</label>
                        <select id="member_filter_status" name="status" class="faq-toolbar__select" data-placeholder="Select status">
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="faq-toolbar__actions">
                        <button type="submit" class="faq-toolbar__btn faq-toolbar__btn--primary">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
                            Filter
                        </button>
                        <button type="button" id="member-filters-reset" class="faq-toolbar__btn">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Reset
                        </button>
                    </div>
                </form>

                <div class="faq-table-wrap">
                    <div class="overflow-x-auto">
                        <table class="faq-table" id="member-main-table">
                            <thead>
                                <tr>
                                    <th class="faq-table__col-question">
                                        <span class="faq-th-inner">Member</span>
                                    </th>
                                    <th class="faq-table__col-category">
                                        <span class="faq-th-inner">Role</span>
                                    </th>
                                    <th class="faq-table__col-status">Status</th>
                                    <th class="faq-table__col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="member-table-body">
                                <tr>
                                    <td colspan="4" class="faq-table__loading">
                                        <div class="faq-skeleton">
                                            <div class="faq-skeleton__row"><div class="faq-skeleton__bar faq-skeleton__bar--q"></div><div class="faq-skeleton__bar faq-skeleton__bar--c"></div><div class="faq-skeleton__bar faq-skeleton__bar--s"></div></div>
                                            <div class="faq-skeleton__row"><div class="faq-skeleton__bar faq-skeleton__bar--q"></div><div class="faq-skeleton__bar faq-skeleton__bar--c"></div><div class="faq-skeleton__bar faq-skeleton__bar--s"></div></div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="member-pagination" class="faq-pagination"></div>
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
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/organization.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/organization-modals.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
    <script src="{{ versioned_asset('js/backend/gallery-editor.js') }}"></script>
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
            heroUpdateUrl: @json(url('/admin/settings/organization/heroes')),
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
        window.orgMemberConfig = {
            indexUrl: @json(route('admin.settings.organization.members.index')),
            storeUrl: @json(route('admin.settings.organization.members.store')),
            updateUrl: @json(url('/admin/settings/organization/members')),
            deleteUrl: @json(url('/admin/settings/organization/members')),
            csrf: @json(csrf_token()),
        };
    </script>
    <script src="{{ versioned_asset('js/components/datetime-picker.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/org-support-hours.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/content-form-shared.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/settings-organization.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/settings-organization-faqs.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/settings-organization-members.js') }}"></script>
@endpush
