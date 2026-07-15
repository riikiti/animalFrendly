<?php

declare(strict_types=1);

namespace App\Modules\Search\Application\DTO;

/**
 * Намеренно без адреса и координат владельца — точную локацию видят только участники
 * начатого контакта (см. Chat/Marketplace), не любой пользователь ленты поиска.
 */
final class PetSearchResult
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $speciesName,
        public readonly ?string $breedName,
        public readonly string $sex,
        public readonly string $purpose,
        public readonly ?string $city,
        public readonly ?float $distanceKm,
        public readonly bool $isVaccinated,
        public readonly bool $isBoosted,
        public readonly ?string $photoUrl,
    ) {}
}
