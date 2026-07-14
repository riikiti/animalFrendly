<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class ShelterNotVerifiedException extends DomainException
{
    public static function create(): self
    {
        return new self('Приют ещё не прошёл верификацию модератором.');
    }
}
