<?php

declare(strict_types=1);

namespace App\Modules\Shop\Domain\Exceptions;

use DomainException;

final class ShopOrderNotFoundException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("Заказ {$id} не найден.");
    }
}
