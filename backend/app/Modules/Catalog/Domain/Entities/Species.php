<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Entities;

final class Species
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $nameRu,
        public readonly bool $isActive,
    ) {}
}
