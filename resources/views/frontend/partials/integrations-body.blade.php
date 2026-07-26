@php
    $integrations = app(\App\Services\Settings\IntegrationsSettingsService::class)->frontendPayload();
    $cookies = $integrations['cookies'];
    $needsConsentGate = $cookies['enabled'] && $cookies['mode'] === 'opt_in';
@endphp

@if(! empty($integrations['gtm_id']) && ! empty($integrations['analytics_enabled']) && ! $needsConsentGate)
    <noscript data-et-gtm-noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id={{ $integrations['gtm_id'] }}"
                height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe>
    </noscript>
@endif

@if(trim((string) $integrations['custom_body']) !== '' && ! empty($integrations['analytics_enabled']) && ! $needsConsentGate)
    <div data-et-custom-body>{!! $integrations['custom_body'] !!}</div>
@endif

@if(! empty($cookies['enabled']))
    <div id="et-cookie-banner" class="et-cookie" hidden role="dialog" aria-live="polite" aria-label="Cookie consent">
        <div class="et-cookie__inner">
            <div class="et-cookie__copy">
                <strong class="et-cookie__title">{{ $cookies['title'] }}</strong>
                <p class="et-cookie__message">{{ $cookies['message'] }}</p>
                @if(! empty($cookies['policy_url']))
                    <a class="et-cookie__policy" href="{{ $cookies['policy_url'] }}">Privacy policy</a>
                @endif
            </div>
            <div class="et-cookie__actions">
                @if(($cookies['mode'] ?? '') !== 'info_only')
                    <button type="button" class="et-btn et-btn--soft et-btn--sm" data-et-cookie-reject>{{ $cookies['reject_label'] }}</button>
                @endif
                <button type="button" class="et-btn et-btn--primary et-btn--sm" data-et-cookie-accept>{{ $cookies['accept_label'] }}</button>
            </div>
        </div>
    </div>
@endif
