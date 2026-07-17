<?php

return [
    'disk' => env('GALLERY_DISK', 'media'),

    'directory' => 'gallery',

    'bin_directory' => 'bin',

    /*
    |--------------------------------------------------------------------------
    | Public URL prefix per disk
    |--------------------------------------------------------------------------
    |
    | Absolute URLs are built as: rtrim(APP_URL, '/') + prefix + '/' + path
    |
    */
    'url_prefixes' => [
        'public' => '/storage',
        'media' => '/media',
    ],

    'max_image_kb' => (int) env('GALLERY_MAX_IMAGE_KB', 5120),
    'max_video_kb' => (int) env('GALLERY_MAX_VIDEO_KB', 51200),
    'max_file_kb' => (int) env('GALLERY_MAX_FILE_KB', 20480),

    'image_mimes' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'],
    'video_mimes' => ['mp4', 'webm', 'ogg', 'mov'],
    'document_mimes' => [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'zip', 'rar', '7z',
    ],

    'per_page_default' => 24,
    'per_page_options' => [12, 24, 48, 96],
];
