<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Queries\ListPendingRequestsForShelter;

final class ListPendingRequestsForShelterQuery
{
    public function __construct(
        public readonly string $shelterId,
        public readonly string $actingUserId,
    ) {}
}
