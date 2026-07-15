<?php

declare(strict_types=1);

return [

    'disk' => env('MEDIA_DISK', env('FILESYSTEM_DISK', 'local')),

    'max_size_kb' => (int) env('MEDIA_MAX_SIZE_KB', 5120),

    'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],

];
