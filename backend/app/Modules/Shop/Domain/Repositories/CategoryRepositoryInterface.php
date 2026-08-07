<?php

declare(strict_types=1);

namespace App\Modules\Shop\Domain\Repositories;

use App\Modules\Shop\Domain\Entities\Category;
use App\Shared\Domain\ValueObjects\Id;

interface CategoryRepositoryInterface
{
    /**
     * @return array<int, Category>
     */
    public function all(): array;

    public function findById(Id $id): ?Category;

    public function findBySlug(string $slug): ?Category;
}
