<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Services;

use App\Modules\Notification\Domain\Entities\Notification;
use App\Modules\Notification\Domain\Enums\NotificationType;
use App\Modules\Notification\Domain\Repositories\NotificationRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

/**
 * Единая точка создания уведомлений — вызывается только из слушателей доменных событий
 * других модулей, см. Infrastructure/Listeners.
 */
final class NotificationDispatcher
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function notify(Id $userId, NotificationType $type, string $message, array $data = []): void
    {
        $notification = Notification::create(
            id: $this->notifications->nextIdentity(),
            userId: $userId,
            type: $type,
            message: $message,
            data: $data,
        );

        $this->notifications->save($notification);
    }
}
