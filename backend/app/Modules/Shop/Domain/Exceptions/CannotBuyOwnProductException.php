<?php

declare(strict_types=1);

namespace App\Modules\Shop\Domain\Exceptions;

use DomainException;

final class CannotBuyOwnProductException extends DomainException
{
    public static function create(): self
    {
        return new self('Нельзя купить собственный товар.');
    }
}
