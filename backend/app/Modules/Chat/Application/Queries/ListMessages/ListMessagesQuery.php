<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Queries\ListMessages;

final class ListMessagesQuery
{
    public function __construct(
        public readonly string $actingUserId,
        public readonly string $conversationId,
        public readonly int $limit = 50,
    ) {}
}
