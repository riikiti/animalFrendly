<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Commands\MarkAllNotificationsRead;

final class MarkAllNotificationsReadCommand
{
    public function __construct(public readonly string $actingUserId) {}
}
