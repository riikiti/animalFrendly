<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Queries\ListBreeds;

use App\Modules\Catalog\Domain\Entities\Breed;
use App\Modules\Catalog\Domain\Exceptions\SpeciesNotFoundException;
use App\Modules\Catalog\Domain\Repositories\BreedRepositoryInterface;
use App\Modules\Catalog\Domain\Repositories\SpeciesRepositoryInterface;

final class ListBreedsHandler
{
    public function __construct(
        private readonly SpeciesRepositoryInterface $species,
        private readonly BreedRepositoryInterface $breeds,
    ) {}

    /**
     * @return list<Breed>
     */
    public function handle(ListBreedsQuery $query): array
    {
        $species = $this->species->findBySlug($query->speciesSlug);

        if ($species === null) {
            throw SpeciesNotFoundException::forSlug($query->speciesSlug);
        }

        return $this->breeds->activeBySpeciesId($species->id);
    }
}
