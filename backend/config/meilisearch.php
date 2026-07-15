<?php

declare(strict_types=1);

return [

    'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
    'key' => env('MEILISEARCH_KEY'),
    'pets_index' => env('MEILISEARCH_PETS_INDEX', 'pets'),
    'listings_index' => env('MEILISEARCH_LISTINGS_INDEX', 'listings'),

];
