<?php

declare(strict_types=1);

namespace App\Modules\Shop\Domain\Exceptions;

use DomainException;

final class ProductNotAvailableException extends DomainException
{
    public static function create(): self
    {
        return new self('Товар снят с продажи.');
    }

    public static function outOfStock(string $title, int $left): self
    {
        return new self("«{$title}»: осталось {$left} шт.");
    }
}
