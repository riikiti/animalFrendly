<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Queries\ListNotifications;

final class ListNotificationsQuery
{
    public function __construct(
        public readonly string $userId,
        public readonly int $limit = 30,
    ) {}
}
