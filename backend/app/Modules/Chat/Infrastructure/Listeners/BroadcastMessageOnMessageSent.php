<?php

declare(strict_types=1);

namespace App\Modules\Chat\Infrastructure\Listeners;

use App\Modules\Chat\Domain\Events\MessageSent;
use App\Modules\Chat\Infrastructure\Broadcasting\MessageBroadcast;

final class BroadcastMessageOnMessageSent
{
    public function handle(MessageSent $event): void
    {
        broadcast(new MessageBroadcast(
            conversationId: $event->conversationId->toString(),
            messageId: $event->messageId->toString(),
            senderId: $event->senderUserId->toString(),
            body: $event->body,
            createdAt: $event->occurredAt()->format(DATE_ATOM),
        ));
    }
}
