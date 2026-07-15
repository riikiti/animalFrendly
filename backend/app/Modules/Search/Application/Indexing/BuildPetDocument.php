<?php

declare(strict_types=1);

namespace App\Modules\Search\Application\Indexing;

use App\Modules\Catalog\Domain\Repositories\BreedRepositoryInterface;
use App\Modules\Catalog\Domain\Repositories\SpeciesRepositoryInterface;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Enums\PetPurpose;

/**
 * Индекс pets — только питомцы, участвующие в подборе/усыновлении (активные, не для продажи —
 * питомцы для продажи представлены отдельно в индексе listings). Город/координаты берутся у
 * владельца (Identity), а не у питомца — см. docs/plan для Search.
 */
final class BuildPetDocument
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly SpeciesRepositoryInterface $species,
        private readonly BreedRepositoryInterface $breeds,
    ) {}

    /**
     * @return array<string, mixed>|null null — питомец не должен быть в индексе (вызывающий код удаляет документ)
     */
    public function build(Pet $pet): ?array
    {
        if (! $pet->isActive() || $pet->purpose() === PetPurpose::ForSale) {
            return null;
        }

        $owner = $this->users->findById($pet->ownerId());
        $species = $this->species->findById($pet->speciesId());
        $breed = $pet->breedId() !== null ? $this->breeds->findById($pet->breedId()) : null;

        $document = [
            'id' => $pet->id()->toString(),
            'name' => $pet->name(),
            'species_id' => $pet->speciesId(),
            'species_name' => $species?->nameRu,
            'breed_id' => $pet->breedId(),
            'breed_name' => $breed?->nameRu,
            'sex' => $pet->sex()->value,
            'purpose' => $pet->purpose()->value,
            'city' => $owner?->city(),
            'is_vaccinated' => $pet->isVaccinated(),
            'is_boosted' => $pet->isBoosted(),
            'photo_url' => $pet->photoUrl(),
        ];

        if ($owner !== null && $owner->hasCoordinates()) {
            $document['_geo'] = ['lat' => $owner->latitude(), 'lng' => $owner->longitude()];
        }

        return $document;
    }
}
