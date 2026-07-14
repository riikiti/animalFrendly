<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class DisputeNotFoundException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("Спор {$id} не найден.");
    }
}
