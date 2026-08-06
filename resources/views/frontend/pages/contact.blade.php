@php
    $email = $siteSettings['contact.email'] ?? null;
    $phone = $siteSettings['contact.phone'] ?? null;
    $whatsapp = $siteSettings['contact.whatsapp'] ?? null;
    $address = $siteSettings['contact.address'] ?? null;
    $hours = $siteSettings['contact.hours'] ?? null;
    $supportHoursRaw = $siteSettings['contact.support_hours'] ?? null;
    $supportHours = is_array($supportHoursRaw)
        ? $supportHoursRaw
        : (is_string($supportHoursRaw) ? (json_decode($supportHoursRaw, true) ?: []) : []);
    $supportHours = \App\Services\Settings\OrganizationSettingsService::normalizeSupportHours(
        is_array($supportHours) ? $supportHours : []
    );
    if ($supportHours === []) {
        $supportHours = \App\Services\Settings\OrganizationSettingsService::defaultSupportHours();
    }
    $dayLabels = \App\Services\Settings\OrganizationSettingsService::supportHourDays();
    $mapsUrl = $siteSettings['contact.maps_url'] ?? null;
    $whatsappHref = $whatsapp
        ? 'https://wa.me/'.preg_replace('/\D+/', '', $whatsapp)
        : null;
    $brand = $siteBrand['name'] ?? 'Examtube.in';

    $mapsEmbedUrl = null;
    if (is_string($mapsUrl) && $mapsUrl !== '') {
        if (str_contains($mapsUrl, '/maps/embed') || str_contains($mapsUrl, 'output=embed')) {
            $mapsEmbedUrl = $mapsUrl;
        } elseif (preg_match('/[?&]q=([^&]+)/', $mapsUrl, $m)) {
            $mapsEmbedUrl = 'https://www.google.com/maps?q='.$m[1].'&output=embed';
        } elseif (preg_match('/@(-?\d+\.?\d*),(-?\d+\.?\d*)/', $mapsUrl, $m)) {
            $mapsEmbedUrl = 'https://www.google.com/maps?q='.$m[1].','.$m[2].'&z=15&output=embed';
        } elseif (str_contains($mapsUrl, 'google.com/maps') || str_contains($mapsUrl, 'maps.google.com') || str_contains($mapsUrl, 'goo.gl/maps')) {
            $sep = str_contains($mapsUrl, '?') ? '&' : '?';
            $mapsEmbedUrl = $mapsUrl.$sep.'output=embed';
        }
    }
    if (! $mapsEmbedUrl && is_string($address) && $address !== '') {
        $mapsEmbedUrl = 'https://www.google.com/maps?q='.rawurlencode($address).'&output=embed';
    }
    if (! $mapsUrl && $mapsEmbedUrl) {
        $mapsUrl = 'https://www.google.com/maps/search/?api=1&query='.rawurlencode((string) $address);
    }

    $supportFaqs = [
        ['q' => 'How quickly do you reply?', 'a' => 'We typically respond within one business day during published support hours.'],
        ['q' => 'Can institutes request onboarding help?', 'a' => 'Yes. Mention institute onboarding in your subject line and we will guide next steps.'],
        ['q' => 'Where can I find product help first?', 'a' => 'Check the FAQs page for common answers about exams, accounts, and learning features.'],
    ];
@endphp

<section class="et-sp-hero et-sp-hero--contact">
    <div class="et-container">
        @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Contact Us'],
        ]])
        <p class="et-eyebrow">{{ $eyebrow ?? 'Support' }}</p>
        <h1>Contact {{ $brand }}</h1>
        <p class="et-sp-hero__lead">
            Questions about exams, accounts, partnerships, or institute onboarding? Reach the team — we are here to help learners and organizations succeed.
        </p>
        @include('frontend.partials.ad-placement', ['page' => 'contact_us', 'position' => 'below_title'])
    </div>
</section>

<section class="et-sp-section">
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => 'Contact details',
            'subtitle' => 'Reach us by email, phone, WhatsApp, or visit the office.',
        ])
        <div class="et-contact-cards">
            @if ($email)
                <a class="et-contact-card" href="mailto:{{ $email }}">
                    <span class="et-contact-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
                    </span>
                    <strong>Email</strong>
                    <span>{{ $email }}</span>
                </a>
            @endif
            @if ($phone)
                <a class="et-contact-card" href="tel:{{ preg_replace('/\s+/', '', $phone) }}">
                    <span class="et-contact-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6.5 3.5h3l1.5 4-2 1.5a12 12 0 006 6l1.5-2 4 1.5v3A2 2 0 0118.5 20 15.5 15.5 0 014 5.5a2 2 0 012.5-2z"/></svg>
                    </span>
                    <strong>Phone</strong>
                    <span>{{ $phone }}</span>
                </a>
            @endif
            @if ($whatsappHref)
                <a class="et-contact-card" href="{{ $whatsappHref }}" target="_blank" rel="noopener noreferrer">
                    <span class="et-contact-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8.5 19.5 6 21l.8-3.2A8 8 0 1112 20a7.8 7.8 0 01-3.5-.9z"/></svg>
                    </span>
                    <strong>WhatsApp</strong>
                    <span>{{ $whatsapp }}</span>
                </a>
            @endif
            @if ($address)
                <div class="et-contact-card">
                    <span class="et-contact-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s7-5.2 7-11a7 7 0 10-14 0c0 5.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    </span>
                    <strong>Office</strong>
                    @if ($mapsUrl)
                        <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer">{{ $address }}</a>
                    @else
                        <span>{{ $address }}</span>
                    @endif
                </div>
            @endif
        </div>
    </div>
</section>

<section class="et-sp-section et-sp-section--soft">
    <div class="et-container et-contact-premium">
        <div class="et-panel et-contact-premium__form">
            <div class="et-contact__form-head">
                <h2 class="et-panel__title">Send us a message</h2>
                <p class="et-panel__subtitle">Share a few details and we will reply during support hours.</p>
            </div>

            <form class="et-form et-form--stack" method="POST" action="{{ route('frontend.contact.store') }}" novalidate data-contact-form>
                @csrf
                <div class="et-form__row">
                    <label class="et-field">
                        <span>Name <abbr class="et-field__req" title="required">*</abbr></span>
                        <input type="text" name="name" value="{{ old('name', auth()->user()->name ?? '') }}" autocomplete="name" data-validate="required|max:120" class="@error('name') is-invalid @enderror">
                        <span class="et-field-error" data-error-for="name" @error('name')@else hidden @enderror>@error('name'){{ $message }}@enderror</span>
                    </label>
                    <label class="et-field">
                        <span>Email <abbr class="et-field__req" title="required">*</abbr></span>
                        <input type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" autocomplete="email" data-validate="required|email|max:190" class="@error('email') is-invalid @enderror">
                        <span class="et-field-error" data-error-for="email" @error('email')@else hidden @enderror>@error('email'){{ $message }}@enderror</span>
                    </label>
                </div>
                <div class="et-form__row">
                    <label class="et-field">
                        <span>Phone <em>(optional)</em></span>
                        <input type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" data-validate="max:40" class="@error('phone') is-invalid @enderror">
                        <span class="et-field-error" data-error-for="phone" @error('phone')@else hidden @enderror>@error('phone'){{ $message }}@enderror</span>
                    </label>
                    <label class="et-field">
                        <span>Subject <em>(optional)</em></span>
                        <input type="text" name="subject" value="{{ old('subject') }}" placeholder="Exam access, institute onboarding, billing…" data-validate="max:190" class="@error('subject') is-invalid @enderror">
                        <span class="et-field-error" data-error-for="subject" @error('subject')@else hidden @enderror>@error('subject'){{ $message }}@enderror</span>
                    </label>
                </div>
                <label class="et-field">
                    <span>Message <abbr class="et-field__req" title="required">*</abbr></span>
                    <textarea name="message" rows="6" placeholder="Tell us how we can help…" data-validate="required|max:5000" class="@error('message') is-invalid @enderror">{{ old('message') }}</textarea>
                    <span class="et-field-error" data-error-for="message" @error('message')@else hidden @enderror>@error('message'){{ $message }}@enderror</span>
                </label>
                <div class="et-form__actions">
                    <button type="submit" class="et-btn et-btn--primary et-btn--lg">Send message</button>
                </div>
                @include('frontend.partials.recaptcha', ['context' => 'contact'])
            </form>
        </div>
        @include('frontend.partials.ad-placement', ['page' => 'contact_us', 'position' => 'after_form'])

        <aside class="et-contact-premium__side">
            <div class="et-panel">
                <h2 class="et-panel__title">Business hours</h2>
                @if ($supportHours !== [])
                    <ul class="et-contact__hours">
                        @foreach ($supportHours as $row)
                            <li>
                                <span class="et-contact__hours-day">{{ $dayLabels[$row['day']] ?? ucfirst($row['day']) }}</span>
                                <span class="et-contact__hours-time">
                                    {{ \App\Services\Settings\OrganizationSettingsService::formatClockAmPm($row['from']) }}
                                    –
                                    {{ \App\Services\Settings\OrganizationSettingsService::formatClockAmPm($row['to']) }}
                                </span>
                                <span class="et-contact__hours-tz">({{ $row['timezone'] === 'Asia/Kolkata' ? 'IST' : $row['timezone'] }})</span>
                            </li>
                        @endforeach
                    </ul>
                @elseif ($hours)
                    <p class="et-contact-premium__note">{{ $hours }}</p>
                @else
                    <p class="et-contact-premium__note">Monday to Friday, standard business hours.</p>
                @endif
            </div>
            @include('frontend.partials.ad-placement', ['page' => 'contact_us', 'position' => 'right_after_hours'])

            @if(($socialLinks ?? collect())->isNotEmpty())
                <div class="et-panel">
                    <h2 class="et-panel__title">Social media</h2>
                    <div class="et-social et-social--contact" aria-label="Social links">
                        @foreach($socialLinks as $link)
                            <a href="{{ $link->url }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               title="{{ $link->label }}"
                               class="et-social__link et-social__link--{{ $link->platform }}">
                                <span class="et-visually-hidden">{{ $link->label }}</span>
                                @include('backend.partials.social-platform-icon', ['platform' => $link->platform, 'size' => 16])
                            </a>
                        @endforeach
                    </div>
                </div>
                @include('frontend.partials.ad-placement', ['page' => 'contact_us', 'position' => 'right_after_social'])
            @endif

            <div class="et-panel">
                <h2 class="et-panel__title">Quick links</h2>
                <ul class="et-cms-aside__links">
                    <li><a href="{{ route('frontend.faqs.index') }}">Browse FAQs</a></li>
                    <li><a href="{{ url('/about-us') }}">About Examtube</a></li>
                    <li><a href="{{ route('frontend.authors.index') }}">Meet authors</a></li>
                </ul>
            </div>
            @include('frontend.partials.ad-placement', ['page' => 'contact_us', 'position' => 'right_after_links'])
        </aside>
    </div>
</section>

<section class="et-sp-section">
    <div class="et-container">
        <div class="et-sp-section__head et-sp-section__head--left">
            <p class="et-eyebrow">Find us</p>
            <h2>Office location</h2>
        </div>
        <div class="et-contact-map">
            @if ($mapsEmbedUrl)
                <div class="et-contact-map__frame" aria-label="Office location map">
                    <iframe
                        src="{{ $mapsEmbedUrl }}"
                        title="Examtube office location"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen
                    ></iframe>
                </div>
                @if ($mapsUrl)
                    <p class="et-contact-map__open">
                        <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer">Open in Google Maps</a>
                    </p>
                @endif
            @else
                <div class="et-contact-map__frame" aria-label="Map placeholder">
                    <div class="et-contact-map__placeholder">
                        <strong>Map placeholder</strong>
                        <span>{{ $address ?: 'Office map will appear here when a maps URL is configured.' }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @include('frontend.partials.ad-placement', ['page' => 'contact_us', 'position' => 'after_map'])
</section>

<section class="et-sp-section et-sp-section--soft">
    <div class="et-container et-sp-faq-wrap">
        <div class="et-sp-section__head et-sp-section__head--left">
            <p class="et-eyebrow">Support FAQ</p>
            <h2>Before you write to us</h2>
        </div>
        <div class="et-faq" data-faq>
            @foreach ($supportFaqs as $i => $faq)
                @php $panelId = 'contact-faq-panel-'.$i; @endphp
                <div class="et-faq__item" data-faq-item>
                    <button type="button" class="et-faq__trigger" data-faq-trigger aria-expanded="false" aria-controls="{{ $panelId }}" id="contact-faq-{{ $i }}">
                        <span>{{ $faq['q'] }}</span>
                        <span class="et-faq__icon" aria-hidden="true">+</span>
                    </button>
                    <div class="et-faq__panel" id="{{ $panelId }}" role="region" aria-labelledby="contact-faq-{{ $i }}">{{ $faq['a'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
    @include('frontend.partials.ad-placement', ['page' => 'contact_us', 'position' => 'after_faqs'])
</section>

<section class="et-sp-cta">
    <div class="et-container et-sp-cta__inner">
        <div>
            <h2>Prefer self-serve help?</h2>
            <p>Browse FAQs or explore the learning catalog while you wait for a reply.</p>
        </div>
        <div class="et-sp-cta__actions">
            <a href="{{ route('frontend.faqs.index') }}" class="et-btn et-btn--primary">Open FAQs</a>
            <a href="{{ route('frontend.exams.index') }}" class="et-btn et-btn--soft">Explore exams</a>
        </div>
    </div>
    @include('frontend.partials.ad-placement', ['page' => 'contact_us', 'position' => 'after_cta'])
</section>
