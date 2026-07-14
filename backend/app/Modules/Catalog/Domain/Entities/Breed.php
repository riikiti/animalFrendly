<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Entities;

final class Breed
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public readonly int $id,
        public readonly int $speciesId,
        public readonly string $slug,
        public readonly string $nameRu,
        public readonly array $attributes,
        public readonly bool $isActive,
    ) {}
}
