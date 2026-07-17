<?php

declare(strict_types=1);

namespace App\Modules\Matching\Application\Queries\ListPendingLikes;

final class ListPendingLikesQuery
{
    public function __construct(
        public readonly string $actingUserId,
        public readonly string $petId,
    ) {}
}
