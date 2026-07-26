@php
    $recaptcha = app(\App\Services\Settings\SecuritySettingsService::class)->frontendRecaptcha();
    $context = $context ?? null;
    $active = $context && ! empty($recaptcha['contexts'][$context]);
@endphp
@if($active)
    <input type="hidden" name="g-recaptcha-response" value="" data-et-recaptcha-token>
    @if(($recaptcha['version'] ?? 'v3') === 'v2')
        <div class="g-recaptcha" data-sitekey="{{ $recaptcha['site_key'] }}" style="margin:.75rem 0"></div>
    @endif
    <script>
        window.ExamtubeRecaptcha = window.ExamtubeRecaptcha || @json($recaptcha);
        window.ExamtubeRecaptcha.context = @json($context);
    </script>
@endif
