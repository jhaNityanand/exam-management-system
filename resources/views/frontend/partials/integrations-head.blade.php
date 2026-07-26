@php
    $integrations = app(\App\Services\Settings\IntegrationsSettingsService::class)->frontendPayload();
    $cookies = $integrations['cookies'];
    $needsConsentGate = $cookies['enabled'] && $cookies['mode'] === 'opt_in';
    $integrationsConfig = [
        'analyticsEnabled' => $integrations['analytics_enabled'],
        'gaId' => $integrations['ga_id'],
        'gtmId' => $integrations['gtm_id'],
        'pixelId' => $integrations['pixel_id'],
        'customHead' => $integrations['custom_head'],
        'customBody' => $integrations['custom_body'],
        'cookies' => $cookies,
        'needsConsentGate' => $needsConsentGate,
    ];
@endphp
<script>
    window.ExamtubeIntegrations = @json($integrationsConfig);
</script>
<script src="{{ versioned_asset('js/frontend/integrations.js') }}" defer></script>
@if(! $needsConsentGate)
    @include('frontend.partials.tracking-tags', ['integrations' => $integrations])
@endif
