<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Queries\GetConversationForMatch;

final class GetConversationForMatchQuery
{
    public function __construct(
        public readonly string $actingUserId,
        public readonly string $matchId,
    ) {}
}
