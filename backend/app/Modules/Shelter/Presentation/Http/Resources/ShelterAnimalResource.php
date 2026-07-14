<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Presentation\Http\Resources;

use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Shelter\Domain\Entities\ShelterAnimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ожидает на входе array{animal: ShelterAnimal, pet: ?Pet} — не просто сущность, а уже
 * обогащённую данными анкеты питомца (Profile), которые нужны для отображения карточки.
 */
final class ShelterAnimalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{animal: ShelterAnimal, pet: ?Pet} $data */
        $data = $this->resource;
        $animal = $data['animal'];
        $pet = $data['pet'];

        return [
            'id' => $animal->id()->toString(),
            'shelter_id' => $animal->shelterId()->toString(),
            'pet_id' => $animal->petId()->toString(),
            'status' => $animal->status()->value,
            'pet' => $pet ? [
                'name' => $pet->name(),
                'species_id' => $pet->speciesId(),
                'breed_id' => $pet->breedId(),
                'sex' => $pet->sex()->value,
                'description' => $pet->description(),
                'is_vaccinated' => $pet->isVaccinated(),
            ] : null,
        ];
    }
}
