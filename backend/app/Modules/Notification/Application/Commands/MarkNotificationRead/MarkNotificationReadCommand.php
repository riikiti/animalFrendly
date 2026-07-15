<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Commands\MarkNotificationRead;

final class MarkNotificationReadCommand
{
    public function __construct(
        public readonly string $notificationId,
        public readonly string $actingUserId,
    ) {}
}
