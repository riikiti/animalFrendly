<?php

declare(strict_types=1);

namespace App\Modules\Shop\Domain\Entities;

use App\Shared\Domain\ValueObjects\Id;

final class Category
{
    public function __construct(
        private readonly Id $id,
        private readonly string $slug,
        private readonly string $name,
        private readonly int $position,
    ) {}

    public function id(): Id
    {
        return $this->id;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function position(): int
    {
        return $this->position;
    }
}
