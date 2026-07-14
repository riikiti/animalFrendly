<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class AdoptionRequestAlreadyDecidedException extends DomainException
{
    public static function create(): self
    {
        return new self('По этой заявке уже принято решение.');
    }
}
