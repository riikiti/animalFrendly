<?php

declare(strict_types=1);

namespace App\Modules\Chat\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class MatchNotFoundException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("Мэтч «{$id}» не найден.");
    }
}
