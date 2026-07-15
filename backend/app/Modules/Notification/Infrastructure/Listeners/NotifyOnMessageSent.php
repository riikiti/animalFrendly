<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Listeners;

use App\Modules\Chat\Domain\Events\MessageSent;
use App\Modules\Notification\Application\Services\NotificationDispatcher;
use App\Modules\Notification\Domain\Enums\NotificationType;

final class NotifyOnMessageSent
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher,
    ) {}

    public function handle(MessageSent $event): void
    {
        $this->dispatcher->notify($event->recipientUserId, NotificationType::NewMessage, 'Новое сообщение', [
            'conversation_id' => $event->conversationId->toString(),
        ]);
    }
}
