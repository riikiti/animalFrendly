<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Commands\CreateConversationForDirectContact;

final class CreateConversationForDirectContactCommand
{
    public function __construct(
        public readonly string $recipientUserId,
        public readonly string $initiatorUserId,
    ) {}
}
