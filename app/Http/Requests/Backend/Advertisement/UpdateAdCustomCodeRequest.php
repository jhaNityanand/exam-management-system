<?php

namespace App\Http\Requests\Backend\Advertisement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdCustomCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \App\Support\AdminCapabilities::userCan($this->user(), \App\Support\AdminCapabilities::ORGANIZATION);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'header_code' => ['nullable', 'string', 'max:200000'],
            'footer_code' => ['nullable', 'string', 'max:200000'],
        ];
    }
}
