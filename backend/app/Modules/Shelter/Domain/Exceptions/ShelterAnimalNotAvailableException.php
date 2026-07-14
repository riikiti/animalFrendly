<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class ShelterAnimalNotAvailableException extends DomainException
{
    public static function create(): self
    {
        return new self('Это животное уже не доступно для пристройства.');
    }
}
