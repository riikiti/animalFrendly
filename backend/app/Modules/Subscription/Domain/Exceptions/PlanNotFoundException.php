<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class PlanNotFoundException extends DomainException
{
    public static function forSlug(string $slug): self
    {
        return new self("Тариф «{$slug}» не найден.");
    }
}
