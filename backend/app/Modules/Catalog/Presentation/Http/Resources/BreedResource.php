<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Resources;

use App\Modules\Catalog\Domain\Entities\Breed;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Breed
 */
final class BreedResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'species_id' => $this->speciesId,
            'slug' => $this->slug,
            'name' => $this->nameRu,
            'attributes' => $this->attributes,
        ];
    }
}
