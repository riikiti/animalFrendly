<?php

declare(strict_types=1);

namespace App\Modules\Search\Presentation\Http\Resources;

use App\Modules\Search\Application\DTO\PetSearchResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PetSearchResult
 */
final class PetSearchResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PetSearchResult $result */
        $result = $this->resource;

        return [
            'id' => $result->id,
            'name' => $result->name,
            'species_name' => $result->speciesName,
            'breed_name' => $result->breedName,
            'sex' => $result->sex,
            'purpose' => $result->purpose,
            'city' => $result->city,
            'distance_km' => $result->distanceKm,
            'is_vaccinated' => $result->isVaccinated,
            'is_boosted' => $result->isBoosted,
            'photo_url' => $result->photoUrl,
        ];
    }
}
