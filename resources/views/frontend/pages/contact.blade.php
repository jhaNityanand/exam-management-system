@php
    $email = $siteSettings['contact.email'] ?? null;
    $phone = $siteSettings['contact.phone'] ?? null;
    $address = $siteSettings['contact.address'] ?? null;
    $hours = $siteSettings['contact.hours'] ?? null;
@endphp

<section class="et-contact">
    <div class="et-contact__intro">
        @if (! empty($page->content))
            <div class="et-prose et-contact__copy">{!! $page->content !!}</div>
        @endif
    </div>

    <div class="et-contact__layout">
        <div class="et-panel et-contact__form-panel">
            <h2 class="et-panel__title">Send us a message</h2>
            <p class="et-panel__subtitle">We typically reply within one business day during support hours.</p>

            @if (session('success'))
                <div class="et-alert et-alert--success" role="status">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="et-alert et-alert--error" role="alert">{{ $errors->first() }}</div>
            @endif

            <form class="et-form et-form--stack" method="POST" action="{{ route('frontend.contact.store') }}">
                @csrf
                <div class="et-form__row">
                    <label class="et-field">
                        <span>Name</span>
                        <input type="text" name="name" value="{{ old('name', auth()->user()->name ?? '') }}" autocomplete="name" required>
                    </label>
                    <label class="et-field">
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" autocomplete="email" required>
                    </label>
                </div>
                <label class="et-field">
                    <span>Subject <em>(optional)</em></span>
                    <input type="text" name="subject" value="{{ old('subject') }}" placeholder="Exam access, institute onboarding, billing…">
                </label>
                <label class="et-field">
                    <span>Message</span>
                    <textarea name="message" rows="6" required placeholder="Tell us how we can help…">{{ old('message') }}</textarea>
                </label>
                <div class="et-form__actions">
                    <button type="submit" class="et-btn et-btn--primary et-btn--lg">Send message</button>
                </div>
            </form>
        </div>

        <aside class="et-panel et-contact__details">
            <h2 class="et-panel__title">Contact details</h2>
            <ul class="et-contact__list">
                @if ($email)
                    <li>
                        <span class="et-contact__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
                        </span>
                        <div>
                            <strong>Email</strong>
                            <a href="mailto:{{ $email }}">{{ $email }}</a>
                        </div>
                    </li>
                @endif
                @if ($phone)
                    <li>
                        <span class="et-contact__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6.5 3.5h3l1.5 4-2 1.5a12 12 0 006 6l1.5-2 4 1.5v3A2 2 0 0118.5 20 15.5 15.5 0 014 5.5a2 2 0 012.5-2z"/></svg>
                        </span>
                        <div>
                            <strong>Phone</strong>
                            <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}">{{ $phone }}</a>
                        </div>
                    </li>
                @endif
                @if ($address)
                    <li>
                        <span class="et-contact__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s7-5.2 7-11a7 7 0 10-14 0c0 5.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        </span>
                        <div>
                            <strong>Address</strong>
                            <span>{{ $address }}</span>
                        </div>
                    </li>
                @endif
                @if ($hours)
                    <li>
                        <span class="et-contact__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        </span>
                        <div>
                            <strong>Hours</strong>
                            <span>{{ $hours }}</span>
                        </div>
                    </li>
                @endif
            </ul>
        </aside>
    </div>
</section>
