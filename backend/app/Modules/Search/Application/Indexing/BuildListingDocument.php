<?php

declare(strict_types=1);

namespace App\Modules\Search\Application\Indexing;

use App\Modules\Catalog\Domain\Repositories\BreedRepositoryInterface;
use App\Modules\Catalog\Domain\Repositories\SpeciesRepositoryInterface;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Marketplace\Domain\Entities\Listing;
use App\Modules\Marketplace\Domain\Enums\ListingStatus;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;

final class BuildListingDocument
{
    public function __construct(
        private readonly PetRepositoryInterface $pets,
        private readonly UserRepositoryInterface $users,
        private readonly SpeciesRepositoryInterface $species,
        private readonly BreedRepositoryInterface $breeds,
    ) {}

    /**
     * @return array<string, mixed>|null null — листинг не должен быть в индексе (вызывающий код удаляет документ)
     */
    public function build(Listing $listing): ?array
    {
        if ($listing->status() !== ListingStatus::Published) {
            return null;
        }

        $pet = $this->pets->findById($listing->petId());

        if ($pet === null) {
            return null;
        }

        $owner = $this->users->findById($listing->sellerId());
        $species = $this->species->findById($pet->speciesId());
        $breed = $pet->breedId() !== null ? $this->breeds->findById($pet->breedId()) : null;

        $document = [
            'id' => $listing->id()->toString(),
            'pet_name' => $pet->name(),
            'species_id' => $pet->speciesId(),
            'species_name' => $species?->nameRu,
            'breed_id' => $pet->breedId(),
            'breed_name' => $breed?->nameRu,
            'city' => $owner?->city(),
            'price_amount' => $listing->price()->minorUnits,
            'currency' => $listing->price()->currency,
            'photo_url' => $pet->photoUrl(),
        ];

        if ($owner !== null && $owner->hasCoordinates()) {
            $document['_geo'] = ['lat' => $owner->latitude(), 'lng' => $owner->longitude()];
        }

        return $document;
    }
}
