<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class NotificationNotFoundException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("Уведомление «{$id}» не найдено.");
    }
}
