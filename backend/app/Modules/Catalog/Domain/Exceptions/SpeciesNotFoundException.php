<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class SpeciesNotFoundException extends DomainException
{
    public static function forSlug(string $slug): self
    {
        return new self("Вид животного «{$slug}» не найден.");
    }
}
