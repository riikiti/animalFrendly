<?php

declare(strict_types=1);

namespace App\Modules\Chat\Infrastructure\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Laravel-нативный broadcast-класс — в отличие от Domain-события MessageSent (framework-
 * агностичного), этот живёт в Infrastructure и явно зависит от фреймворка. Транслируется через
 * уже работающую Redis-очередь (ShouldBroadcast подразумевает queued broadcasting), без новой
 * инфраструктуры. Форма payload дословно повторяет MessageResource::toArray(), чтобы фронтенд
 * мог положить событие напрямую в список Message[] без отдельного маппинга.
 */
final class MessageBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $conversationId,
        public readonly string $messageId,
        public readonly string $senderId,
        public readonly string $body,
        public readonly string $createdAt,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("conversation.{$this->conversationId}")];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->messageId,
            'conversation_id' => $this->conversationId,
            'sender_id' => $this->senderId,
            'body' => $this->body,
            'read_at' => null,
            'created_at' => $this->createdAt,
        ];
    }
}
