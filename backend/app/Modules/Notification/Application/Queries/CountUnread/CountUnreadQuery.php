<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Queries\CountUnread;

final class CountUnreadQuery
{
    public function __construct(public readonly string $userId) {}
}
