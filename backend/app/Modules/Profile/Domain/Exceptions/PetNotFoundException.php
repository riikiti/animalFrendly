<?php

declare(strict_types=1);

namespace App\Modules\Profile\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class PetNotFoundException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("Анкета питомца «{$id}» не найдена.");
    }
}
