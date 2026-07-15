<?php

declare(strict_types=1);

return [

    // 'public', а не FILESYSTEM_DISK по умолчанию — фото должны быть доступны по абсолютному
    // URL с отдельного origin (фронтенд на другом порту/домене), а не только через диск
    // "local" с serve=>true, который отдаёт относительный путь без хоста.
    'disk' => env('MEDIA_DISK', 'public'),

    'max_size_kb' => (int) env('MEDIA_MAX_SIZE_KB', 5120),

    'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],

];
