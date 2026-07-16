<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Laravel-нативный broadcast-класс, транслируемый из единой точки создания уведомлений
 * (NotificationDispatcher::notify()) — покрывает все типы уведомлений (new_match,
 * new_message, adoption_approved, deal_completed) одним каналом, без доработки каждого
 * слушателя под резолвинг адресата. Форма payload дословно повторяет
 * NotificationResource::toArray(), см. Chat\Infrastructure\Broadcasting\MessageBroadcast
 * для того же приёма.
 */
final class NotificationBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $notificationId,
        public readonly string $type,
        public readonly string $message,
        public readonly array $data,
        public readonly string $createdAt,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->userId}")];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notificationId,
            'type' => $this->type,
            'message' => $this->message,
            'data' => $this->data,
            'read_at' => null,
            'created_at' => $this->createdAt,
        ];
    }
}
