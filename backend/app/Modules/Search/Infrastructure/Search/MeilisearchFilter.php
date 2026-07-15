<?php

declare(strict_types=1);

namespace App\Modules\Search\Infrastructure\Search;

final class MeilisearchFilter
{
    public static function quote(string $value): string
    {
        return '"'.str_replace('"', '\\"', $value).'"';
    }
}
