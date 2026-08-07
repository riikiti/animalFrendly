<?php

declare(strict_types=1);

namespace App\Modules\Shop\Domain\Exceptions;

use DomainException;

final class ProductNotFoundException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("Товар {$id} не найден.");
    }
}
