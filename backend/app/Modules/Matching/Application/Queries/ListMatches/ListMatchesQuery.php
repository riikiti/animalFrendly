<?php

declare(strict_types=1);

namespace App\Modules\Matching\Application\Queries\ListMatches;

final class ListMatchesQuery
{
    public function __construct(
        public readonly string $actingUserId,
        public readonly string $petId,
    ) {}
}
