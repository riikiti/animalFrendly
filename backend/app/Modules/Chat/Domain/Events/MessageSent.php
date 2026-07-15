<?php

declare(strict_types=1);

namespace App\Modules\Chat\Domain\Events;

use App\Shared\Domain\DomainEvent;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

/**
 * Публикуется при отправке сообщения. Модуль Notification подписывается на это событие,
 * чтобы уведомить получателя — межмодульная связь через событие, а не прямая зависимость,
 * см. docs/plan/03-architecture.md.
 */
final class MessageSent implements DomainEvent
{
    public function __construct(
        public readonly Id $conversationId,
        public readonly Id $messageId,
        public readonly Id $senderUserId,
        public readonly Id $recipientUserId,
        public readonly string $body,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
