<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Adapters;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Notification\Application\Contracts\UserEmailLookupInterface;
use App\Shared\Domain\ValueObjects\Id;

/**
 * Единственное место, где модуль Identity "знает" про Notification — реализация чужого
 * Application-контракта, байндится в IdentityServiceProvider::register(), см.
 * docs/rules/01-backend.md.
 */
final class NotificationUserEmailLookup implements UserEmailLookupInterface
{
    public function emailFor(Id $userId): ?string
    {
        return User::query()->find($userId->toString())?->email;
    }
}
