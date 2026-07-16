<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Adapters;

use App\Modules\Identity\Application\Contracts\GeocoderInterface as IdentityGeocoderInterface;
use App\Modules\Shelter\Application\Contracts\GeocodedAddress as ShelterGeocodedAddress;
use App\Modules\Shelter\Application\Contracts\GeocoderInterface as ShelterGeocoderInterface;

/**
 * Единственное место, где Identity "знает" про Shelter — реализация чужого
 * Application-контракта, байндится в IdentityServiceProvider::register(), см.
 * docs/rules/01-backend.md. Оборачивает уже существующий Identity\...\GeocoderInterface
 * (→ YandexGeocoderClient/NullGeocoderClient) — HTTP-логика геокодирования не дублируется.
 */
final class ShelterGeocoderAdapter implements ShelterGeocoderInterface
{
    public function __construct(private readonly IdentityGeocoderInterface $geocoder) {}

    public function geocode(string $address): ?ShelterGeocodedAddress
    {
        $geocoded = $this->geocoder->geocode($address);

        if ($geocoded === null) {
            return null;
        }

        return new ShelterGeocodedAddress(
            formattedAddress: $geocoded->formattedAddress,
            city: $geocoded->city,
            latitude: $geocoded->latitude,
            longitude: $geocoded->longitude,
        );
    }
}
