<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class YookassaRequestFailedException extends DomainException
{
    public static function create(string $operation, string $details): self
    {
        return new self("Запрос к ЮKassa ({$operation}) завершился ошибкой: {$details}");
    }
}
