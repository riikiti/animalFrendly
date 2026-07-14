<?php

declare(strict_types=1);

namespace App\Modules\Profile\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class SpeciesNotFoundException extends DomainException
{
    public static function forId(int $speciesId): self
    {
        return new self("Вид животного #{$speciesId} не найден.");
    }
}
