<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class OrderAlreadyConfirmedException extends DomainException
{
    public static function create(): self
    {
        return new self('Вы уже подтвердили эту сделку.');
    }
}
