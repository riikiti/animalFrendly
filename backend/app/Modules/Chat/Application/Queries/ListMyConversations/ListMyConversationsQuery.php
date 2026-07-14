<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Queries\ListMyConversations;

final class ListMyConversationsQuery
{
    public function __construct(public readonly string $actingUserId) {}
}
