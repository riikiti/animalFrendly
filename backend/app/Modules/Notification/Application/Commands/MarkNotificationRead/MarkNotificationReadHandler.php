<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Commands\MarkNotificationRead;

use App\Modules\Notification\Domain\Entities\Notification;
use App\Modules\Notification\Domain\Exceptions\NotificationNotFoundException;
use App\Modules\Notification\Domain\Exceptions\NotificationNotOwnedByActorException;
use App\Modules\Notification\Domain\Repositories\NotificationRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class MarkNotificationReadHandler
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications) {}

    public function handle(MarkNotificationReadCommand $command): Notification
    {
        $notification = $this->notifications->findById(Id::fromString($command->notificationId));

        if ($notification === null) {
            throw NotificationNotFoundException::forId($command->notificationId);
        }

        if (! $notification->userId()->equals(Id::fromString($command->actingUserId))) {
            throw NotificationNotOwnedByActorException::create();
        }

        $notification->markRead();
        $this->notifications->save($notification);

        return $notification;
    }
}
