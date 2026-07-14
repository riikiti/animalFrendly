<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class ShelterAnimalNotFoundException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("Карточка животного «{$id}» не найдена.");
    }
}
