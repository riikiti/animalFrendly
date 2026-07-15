<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Repositories;

use App\Modules\Notification\Domain\Entities\Notification;
use App\Shared\Domain\ValueObjects\Id;

interface NotificationRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(Notification $notification): void;

    public function findById(Id $id): ?Notification;

    /**
     * @return list<Notification>
     */
    public function findByUser(Id $userId, int $limit): array;

    public function countUnreadForUser(Id $userId): int;

    public function markAllReadForUser(Id $userId): void;
}
