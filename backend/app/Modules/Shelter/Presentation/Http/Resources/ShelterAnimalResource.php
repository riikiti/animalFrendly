<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Presentation\Http\Resources;

use App\Modules\Shelter\Domain\Entities\ShelterAnimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShelterAnimal
 */
final class ShelterAnimalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ShelterAnimal $animal */
        $animal = $this->resource;

        return [
            'id' => $animal->id()->toString(),
            'shelter_id' => $animal->shelterId()->toString(),
            'pet_id' => $animal->petId()->toString(),
            'status' => $animal->status()->value,
        ];
    }
}
