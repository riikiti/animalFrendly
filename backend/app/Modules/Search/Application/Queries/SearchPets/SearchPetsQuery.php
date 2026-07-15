<?php

declare(strict_types=1);

namespace App\Modules\Search\Application\Queries\SearchPets;

final class SearchPetsQuery
{
    public function __construct(
        public readonly string $actingUserId,
        public readonly ?int $speciesId = null,
        public readonly ?int $breedId = null,
        public readonly ?string $sex = null,
        public readonly ?string $purpose = null,
        public readonly ?string $city = null,
        public readonly ?bool $isVaccinated = null,
        public readonly ?float $radiusKm = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
