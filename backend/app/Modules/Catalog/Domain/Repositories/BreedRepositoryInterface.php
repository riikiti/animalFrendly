<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Repositories;

use App\Modules\Catalog\Domain\Entities\Breed;

interface BreedRepositoryInterface
{
    /**
     * @return list<Breed>
     */
    public function activeBySpeciesId(int $speciesId): array;
}
