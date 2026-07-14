<?php

declare(strict_types=1);

namespace App\Modules\Profile\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class BreedDoesNotBelongToSpeciesException extends DomainException
{
    public static function create(int $breedId, int $speciesId): self
    {
        return new self("Порода #{$breedId} не относится к виду #{$speciesId}.");
    }
}
