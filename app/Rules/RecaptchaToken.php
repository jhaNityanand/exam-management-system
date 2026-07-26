<?php

namespace App\Rules;

use App\Services\Settings\SecuritySettingsService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RecaptchaToken implements ValidationRule
{
    public function __construct(
        protected string $context = 'submit',
        protected ?int $orgId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $security = app(SecuritySettingsService::class);

        if (! $security->requiresRecaptcha($this->context, $this->orgId)) {
            return;
        }

        $result = $security->verify(is_string($value) ? $value : null, $this->context, $this->orgId);
        if (! $result['ok']) {
            $fail($result['message'] ?? 'reCAPTCHA verification failed.');
        }
    }
}
