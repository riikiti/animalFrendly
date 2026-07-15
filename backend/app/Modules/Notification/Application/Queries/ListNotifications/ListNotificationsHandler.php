<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Queries\ListNotifications;

use App\Modules\Notification\Domain\Entities\Notification;
use App\Modules\Notification\Domain\Repositories\NotificationRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class ListNotificationsHandler
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications) {}

    /**
     * @return list<Notification>
     */
    public function handle(ListNotificationsQuery $query): array
    {
        return $this->notifications->findByUser(Id::fromString($query->userId), $query->limit);
    }
}
