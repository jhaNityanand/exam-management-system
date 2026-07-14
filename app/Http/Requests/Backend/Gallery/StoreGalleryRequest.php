<?php

namespace App\Http\Requests\Backend\Gallery;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreGalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $mimes = array_values(array_unique(array_merge(
            config('gallery.image_mimes', []),
            config('gallery.video_mimes', []),
            config('gallery.document_mimes', [])
        )));

        $maxKb = max(
            (int) config('gallery.max_image_kb', 5120),
            (int) config('gallery.max_video_kb', 51200),
            (int) config('gallery.max_file_kb', 20480)
        );

        $fileRule = ['required', File::types($mimes)->max($maxKb)];

        return [
            'files' => ['nullable', 'array', 'max:20'],
            'files.*' => $fileRule,
            'file' => array_merge(['nullable'], array_slice($fileRule, 1)),
            'alt_text' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasFiles = $this->hasFile('files') || $this->hasFile('file');
            if (! $hasFiles) {
                $validator->errors()->add('files', 'Please select at least one file to upload.');
            }
        });
    }

    /**
     * @return list<\Illuminate\Http\UploadedFile>
     */
    public function uploadedFiles(): array
    {
        $files = $this->file('files', []);
        if (! is_array($files)) {
            $files = $files ? [$files] : [];
        }

        if ($this->hasFile('file')) {
            $files[] = $this->file('file');
        }

        return array_values(array_filter($files));
    }
}
