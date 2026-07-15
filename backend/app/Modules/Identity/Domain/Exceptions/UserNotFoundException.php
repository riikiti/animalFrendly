<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class UserNotFoundException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("Пользователь «{$id}» не найден.");
    }
}
