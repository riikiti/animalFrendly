<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\CreateListing;

final class CreateListingCommand
{
    public function __construct(
        public readonly string $sellerId,
        public readonly int $speciesId,
        public readonly ?int $breedId,
        public readonly string $name,
        public readonly string $sex,
        public readonly ?string $birthdate,
        public readonly ?string $description,
        public readonly bool $isVaccinated,
        public readonly int $priceAmount,
        public readonly string $currency = 'RUB',
    ) {}
}
