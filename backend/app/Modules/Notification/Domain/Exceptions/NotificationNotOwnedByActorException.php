<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class NotificationNotOwnedByActorException extends DomainException
{
    public static function create(): self
    {
        return new self('Отметить прочитанным можно только своё уведомление.');
    }
}
