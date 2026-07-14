<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Commands\SendMessage;

final class SendMessageCommand
{
    public function __construct(
        public readonly string $actingUserId,
        public readonly string $conversationId,
        public readonly string $body,
    ) {}
}
