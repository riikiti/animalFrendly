<?php

declare(strict_types=1);

namespace App\Modules\Search\Presentation\Http\Resources;

use App\Modules\Search\Application\DTO\ListingSearchResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ListingSearchResult
 */
final class ListingSearchResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ListingSearchResult $result */
        $result = $this->resource;

        return [
            'id' => $result->id,
            'pet_name' => $result->petName,
            'species_name' => $result->speciesName,
            'breed_name' => $result->breedName,
            'city' => $result->city,
            'distance_km' => $result->distanceKm,
            'price_amount' => $result->priceAmount,
            'currency' => $result->currency,
            'photo_url' => $result->photoUrl,
        ];
    }
}
