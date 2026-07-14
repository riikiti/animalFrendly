<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Commands\CreateConversationForMatch;

final class CreateConversationForMatchCommand
{
    public function __construct(public readonly string $matchId) {}
}
