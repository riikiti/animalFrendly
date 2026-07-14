<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Presentation\Http\Resources;

use App\Modules\Marketplace\Domain\Entities\Listing;
use App\Modules\Profile\Domain\Entities\Pet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ожидает на входе array{listing: Listing, pet: ?Pet} — как ShelterAnimalResource, см.
 * Shelter\Presentation\Http\Resources\ShelterAnimalResource.
 */
final class ListingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{listing: Listing, pet: ?Pet} $data */
        $data = $this->resource;
        $listing = $data['listing'];
        $pet = $data['pet'];

        return [
            'id' => $listing->id()->toString(),
            'seller_id' => $listing->sellerId()->toString(),
            'pet_id' => $listing->petId()->toString(),
            'price_amount' => $listing->price()->minorUnits,
            'currency' => $listing->price()->currency,
            'status' => $listing->status()->value,
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
