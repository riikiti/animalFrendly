<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class OrderNotFoundException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("Заказ {$id} не найден.");
    }
}
