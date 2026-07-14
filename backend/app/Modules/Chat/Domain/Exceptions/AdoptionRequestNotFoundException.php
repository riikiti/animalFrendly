<?php

declare(strict_types=1);

namespace App\Modules\Chat\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class AdoptionRequestNotFoundException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("Заявка на усыновление «{$id}» не найдена.");
    }
}
