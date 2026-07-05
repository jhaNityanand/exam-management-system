<?php

namespace App\Http\Requests\Backend\Question;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return (new StoreQuestionRequest())->rules();
    }
}
