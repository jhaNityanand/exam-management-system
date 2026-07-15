<?php

namespace App\Http\Requests\Backend\Gallery;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class SaveGalleryEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $mimes = config('gallery.image_mimes', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        $maxKb = (int) config('gallery.max_image_kb', 5120);

        return [
            'file' => ['required', File::types($mimes)->max($maxKb)],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }
}
