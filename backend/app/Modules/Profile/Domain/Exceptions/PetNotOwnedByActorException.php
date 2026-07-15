<?php

declare(strict_types=1);

namespace App\Modules\Profile\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class PetNotOwnedByActorException extends DomainException
{
    public static function create(): self
    {
        return new self('Управлять можно только анкетой своего питомца.');
    }
}
