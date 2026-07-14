<?php

declare(strict_types=1);

namespace App\Modules\Matching\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class PetAlreadySwipedException extends DomainException
{
    public static function create(): self
    {
        return new self('Эта анкета уже была просвайпана.');
    }
}
