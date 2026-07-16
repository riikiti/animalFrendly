<?php

declare(strict_types=1);

namespace App\Modules\Profile\Presentation\Http\Resources;

use App\Modules\Profile\Domain\Entities\Pet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pet
 */
final class PetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Pet $pet */
        $pet = $this->resource;

        return [
            'id' => $pet->id()->toString(),
            'owner_id' => $pet->ownerId()->toString(),
            'species_id' => $pet->speciesId(),
            'breed_id' => $pet->breedId(),
            'name' => $pet->name(),
            'sex' => $pet->sex()->value,
            'birthdate' => $pet->birthdate()?->format('Y-m-d'),
            'purpose' => $pet->purpose()->value,
            'description' => $pet->description(),
            'is_vaccinated' => $pet->isVaccinated(),
            'status' => $pet->status()->value,
            'photo_url' => $pet->photoUrl(),
            'social_tags' => array_map(fn ($tag) => $tag->value, $pet->socialTags()),
        ];
    }
}
