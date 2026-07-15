<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class AccountBlockedException extends DomainException
{
    public static function create(): self
    {
        return new self('Аккаунт заблокирован.');
    }
}
