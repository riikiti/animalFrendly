<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Repositories;

use App\Modules\Catalog\Domain\Entities\Species;

interface SpeciesRepositoryInterface
{
    /**
     * @return list<Species>
     */
    public function allActive(): array;

    public function findBySlug(string $slug): ?Species;

    public function findById(int $id): ?Species;
}
