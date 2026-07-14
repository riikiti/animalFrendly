<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use App\Shared\Domain\Exceptions\DomainException;

final class PhoneAlreadyRegisteredException extends DomainException
{
    public static function forPhone(PhoneNumber $phone): self
    {
        return new self("Пользователь с телефоном {$phone} уже зарегистрирован.");
    }
}
