@php
    $ctaLabel = $cta ?? ($siteSettings['newsletter.cta'] ?? 'Subscribe');
    $formAction = Route::has('frontend.newsletter.store') ? route('frontend.newsletter.store') : '#';
    $isCompact = $compact ?? false;
@endphp
<form
    class="et-newsletter-form {{ $isCompact ? 'et-newsletter-form--compact' : '' }}"
    action="{{ $formAction }}"
    method="POST"
    data-newsletter-form
>
    @csrf
    <input type="email" name="email" placeholder="Your email address" required autocomplete="email" aria-label="Email">
    @unless($isCompact)
        <input type="text" name="name" placeholder="Name (optional)" autocomplete="name" aria-label="Name">
    @endunless
    <input type="hidden" name="source" value="{{ $source ?? 'website' }}">
    <button type="submit" class="et-btn">{{ $ctaLabel }}</button>
    <div class="et-newsletter-form__msg" data-newsletter-msg role="status" aria-live="polite"></div>
</form>
