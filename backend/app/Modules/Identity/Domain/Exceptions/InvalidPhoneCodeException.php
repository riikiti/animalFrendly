<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use DomainException;

final class InvalidPhoneCodeException extends DomainException
{
    public static function create(): self
    {
        return new self('Код неверный или устарел. Запросите новый.');
    }
}
