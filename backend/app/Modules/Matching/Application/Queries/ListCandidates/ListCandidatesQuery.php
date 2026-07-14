<?php

declare(strict_types=1);

namespace App\Modules\Matching\Application\Queries\ListCandidates;

final class ListCandidatesQuery
{
    public function __construct(
        public readonly string $actingUserId,
        public readonly string $swiperPetId,
        public readonly int $limit = 20,
    ) {}
}
