<?php

declare(strict_types=1);

namespace App\Modules\Search\Infrastructure\Search;

use Meilisearch\Client;

final class MeilisearchClientFactory
{
    public static function make(): Client
    {
        return new Client(
            (string) config('meilisearch.host'),
            config('meilisearch.key') !== null ? (string) config('meilisearch.key') : null,
        );
    }
}
