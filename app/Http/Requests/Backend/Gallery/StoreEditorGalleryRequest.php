<?php

namespace App\Http\Requests\Backend\Gallery;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreEditorGalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $kind = $this->input('kind', 'file');

        $maxKb = match ($kind) {
            'image' => (int) config('editor.max_image_kb', 2048),
            'video' => (int) config('editor.max_video_kb', 20480),
            default => (int) config('editor.max_file_kb', 10240),
        };

        $mimes = match ($kind) {
            'image' => config('editor.image_mimes', []),
            'video' => config('editor.video_mimes', []),
            default => config('editor.file_mimes', []),
        };

        return [
            'kind' => ['nullable', 'in:image,video,file'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'file' => [
                'required',
                File::types($mimes)->max($maxKb),
            ],
            // Optional raw original when uploading an adjusted image variant.
            'original' => [
                'nullable',
                File::types(config('editor.image_mimes', []))->max(
                    max($maxKb, (int) config('editor.max_image_kb', 2048) * 2)
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.max' => 'The uploaded file exceeds the allowed size limit.',
            'file' => 'This file type is not allowed or is too large.',
            'original' => 'The original image is not allowed or is too large.',
        ];
    }
}
