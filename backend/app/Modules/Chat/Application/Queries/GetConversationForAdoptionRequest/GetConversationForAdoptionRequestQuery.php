<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Queries\GetConversationForAdoptionRequest;

final class GetConversationForAdoptionRequestQuery
{
    public function __construct(
        public readonly string $actingUserId,
        public readonly string $adoptionRequestId,
    ) {}
}
