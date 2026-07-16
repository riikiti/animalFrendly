<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Contracts;

final class GeocodedAddress
{
    public function __construct(
        public readonly string $formattedAddress,
        public readonly ?string $city,
        public readonly float $latitude,
        public readonly float $longitude,
    ) {}
}
