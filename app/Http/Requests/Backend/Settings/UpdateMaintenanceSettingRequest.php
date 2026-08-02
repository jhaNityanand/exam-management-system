<?php

namespace App\Http\Requests\Backend\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateMaintenanceSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:50000'],
            'estimated_at' => ['nullable', 'string', 'max:50'],
            'social_facebook' => ['nullable', 'url', 'max:255'],
            'social_instagram' => ['nullable', 'url', 'max:255'],
            'social_linkedin' => ['nullable', 'url', 'max:255'],
            'social_twitter' => ['nullable', 'url', 'max:255'],
            'social_youtube' => ['nullable', 'url', 'max:255'],
            'social_telegram' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $this->input('message', ''))) ?? '');
            if ($plain === '') {
                $validator->errors()->add('message', 'Please enter a maintenance message.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'enabled' => filter_var($this->input('enabled'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please enter a maintenance title.',
            'message.required' => 'Please enter a maintenance message.',
        ];
    }
}
