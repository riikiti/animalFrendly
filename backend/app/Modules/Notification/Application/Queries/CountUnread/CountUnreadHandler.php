<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Queries\CountUnread;

use App\Modules\Notification\Domain\Repositories\NotificationRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class CountUnreadHandler
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications) {}

    public function handle(CountUnreadQuery $query): int
    {
        return $this->notifications->countUnreadForUser(Id::fromString($query->userId));
    }
}
