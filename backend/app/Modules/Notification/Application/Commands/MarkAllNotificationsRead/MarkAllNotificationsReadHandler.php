<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Commands\MarkAllNotificationsRead;

use App\Modules\Notification\Domain\Repositories\NotificationRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class MarkAllNotificationsReadHandler
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications) {}

    public function handle(MarkAllNotificationsReadCommand $command): void
    {
        $this->notifications->markAllReadForUser(Id::fromString($command->actingUserId));
    }
}
