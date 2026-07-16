<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Presentation\Http\Resources;

use App\Modules\Marketplace\Domain\Entities\Listing;
use App\Modules\Profile\Domain\Entities\Pet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ожидает на входе array{listing: Listing, pet: ?Pet, parent_name?: ?string,
 * seller_name?: ?string, seller_avatar_url?: ?string} — как ShelterAnimalResource, см.
 * Shelter\Presentation\Http\Resources\ShelterAnimalResource. Опциональные ключи по
 * умолчанию null для мест, где они ещё не подгружены.
 */
final class ListingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{listing: Listing, pet: ?Pet, parent_name?: ?string, seller_name?: ?string, seller_avatar_url?: ?string} $data */
        $data = $this->resource;
        $listing = $data['listing'];
        $pet = $data['pet'];

        return [
            'id' => $listing->id()->toString(),
            'seller_id' => $listing->sellerId()->toString(),
            'seller_name' => $data['seller_name'] ?? null,
            'seller_avatar_url' => $data['seller_avatar_url'] ?? null,
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
                'parent_pet_id' => $pet->parentId()?->toString(),
                'parent_name' => $data['parent_name'] ?? null,
            ] : null,
        ];
    }
}
