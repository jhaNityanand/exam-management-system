<?php

namespace App\Http\Requests\Backend\Gallery;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * Commit a single staged gallery file (and optional edited version).
 *
 * - file: the file to display (modified if edited, otherwise the original)
 * - original: optional untouched original when the user edited before save
 */
class CommitGalleryRequest extends FormRequest
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

        $imageMimes = config('gallery.image_mimes', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        $maxImageKb = (int) config('gallery.max_image_kb', 5120);

        return [
            'file' => ['required', File::types($mimes)->max($maxKb)],
            'original' => ['nullable', File::types($imageMimes)->max($maxImageKb)],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:50'],
        ];
    }
}
