<?php

declare(strict_types=1);

namespace App\Modules\Search\Application\DTO;

final class ListingSearchResult
{
    public function __construct(
        public readonly string $id,
        public readonly string $petName,
        public readonly ?string $speciesName,
        public readonly ?string $breedName,
        public readonly ?string $city,
        public readonly ?float $distanceKm,
        public readonly int $priceAmount,
        public readonly string $currency,
        public readonly ?string $photoUrl,
    ) {}
}
