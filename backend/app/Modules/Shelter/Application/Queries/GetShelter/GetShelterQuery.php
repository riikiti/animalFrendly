<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Queries\GetShelter;

final class GetShelterQuery
{
    public function __construct(
        public readonly string $shelterId,
    ) {}
}
