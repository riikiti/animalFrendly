<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Queries\ListBreeds;

final class ListBreedsQuery
{
    public function __construct(public readonly string $speciesSlug) {}
}
