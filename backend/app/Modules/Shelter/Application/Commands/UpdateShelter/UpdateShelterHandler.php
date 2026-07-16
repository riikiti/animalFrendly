<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Commands\UpdateShelter;

use App\Modules\Shelter\Application\Contracts\GeocoderInterface;
use App\Modules\Shelter\Domain\Entities\Shelter;
use App\Modules\Shelter\Domain\Exceptions\NotShelterOwnerException;
use App\Modules\Shelter\Domain\Exceptions\ShelterNotFoundException;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class UpdateShelterHandler
{
    public function __construct(
        private readonly ShelterRepositoryInterface $shelters,
        private readonly GeocoderInterface $geocoder,
    ) {}

    public function handle(UpdateShelterCommand $command): Shelter
    {
        $shelter = $this->shelters->findById(Id::fromString($command->shelterId));

        if ($shelter === null) {
            throw ShelterNotFoundException::forId($command->shelterId);
        }

        if (! $shelter->ownerUserId()->equals(Id::fromString($command->actingUserId))) {
            throw NotShelterOwnerException::create();
        }

        $shelter->setContactInfo($command->phone, $command->email);

        if ($command->address === null || trim($command->address) === '') {
            $shelter->setLocation(null, null, null, null);
            $this->shelters->save($shelter);

            return $shelter;
        }

        $geocoded = $this->geocoder->geocode($command->address);

        $shelter->setLocation(
            $command->address,
            $geocoded?->city,
            $geocoded?->latitude,
            $geocoded?->longitude,
        );
        $this->shelters->save($shelter);

        return $shelter;
    }
}
