<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class InvalidPhoneNumberException extends DomainException
{
    public static function forValue(string $value): self
    {
        return new self("«{$value}» не является корректным российским номером телефона.");
    }
}
