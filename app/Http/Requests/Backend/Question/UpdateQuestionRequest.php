<?php

namespace App\Http\Requests\Backend\Question;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \App\Support\AdminCapabilities::userCan($this->user(), \App\Support\AdminCapabilities::CONTENT);
    }

    public function rules(): array
    {
        return (new StoreQuestionRequest())->rules();
    }
}
