<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Queries\ListSpecies;

use App\Modules\Catalog\Domain\Entities\Species;
use App\Modules\Catalog\Domain\Repositories\SpeciesRepositoryInterface;

final class ListSpeciesHandler
{
    public function __construct(private readonly SpeciesRepositoryInterface $species) {}

    /**
     * @return list<Species>
     */
    public function handle(): array
    {
        return $this->species->allActive();
    }
}
