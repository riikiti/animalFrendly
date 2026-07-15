<?php

declare(strict_types=1);

namespace App\Modules\Search\Application\Queries\SearchListings;

final class SearchListingsQuery
{
    public function __construct(
        public readonly string $actingUserId,
        public readonly ?string $q = null,
        public readonly ?int $speciesId = null,
        public readonly ?int $breedId = null,
        public readonly ?string $city = null,
        public readonly ?int $minPriceAmount = null,
        public readonly ?int $maxPriceAmount = null,
        public readonly ?float $radiusKm = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
