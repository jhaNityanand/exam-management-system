<?php

namespace App\Http\Requests\Backend\Settings;

use App\Models\User;
use App\Models\UserOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreOrganizationMemberRequest extends FormRequest
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
        $memberParam = $this->route('member');
        $memberId = $memberParam instanceof UserOrganization
            ? (int) $memberParam->getKey()
            : (int) $memberParam;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH') || $memberId > 0;

        $emailRules = [
            $isUpdate ? 'sometimes' : 'required',
            'email:rfc',
            'max:190',
        ];

        // On update, keep emails unique (ignore the member's current user).
        // On create, existing accounts may be invited — uniqueness is not required.
        if ($isUpdate && $memberId > 0) {
            $userId = $memberParam instanceof UserOrganization
                ? (int) $memberParam->user_id
                : UserOrganization::query()->whereKey($memberId)->value('user_id');

            $emailUnique = Rule::unique('users', 'email')->whereNull('deleted_at');
            if ($userId) {
                $emailUnique = $emailUnique->ignore($userId);
            }
            $emailRules[] = $emailUnique;
        }

        return [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:120'],
            'email' => $emailRules,
            'password' => [
                'nullable',
                'string',
                Password::defaults(),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->isMethod('PUT') || $this->isMethod('PATCH') || (int) $this->route('member')) {
                return;
            }

            $email = strtolower(trim((string) $this->input('email', '')));
            if ($email === '') {
                return;
            }

            $exists = User::query()->where('email', $email)->exists();
            if (! $exists && ! filled($this->input('password'))) {
                $validator->errors()->add('password', 'A password is required when creating a new member.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
        }

        if ($this->input('password') === '') {
            $this->merge(['password' => null]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the member name.',
            'email.required' => 'Please enter the member email.',
            'email.email' => 'Enter a valid email address.',
            'status.required' => 'Please select a status.',
        ];
    }
}
