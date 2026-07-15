<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class FcmRequestFailedException extends DomainException
{
    public static function create(string $operation, string $details): self
    {
        return new self("Запрос к FCM ({$operation}) завершился ошибкой: {$details}");
    }
}
