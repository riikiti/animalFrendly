<?php

declare(strict_types=1);

namespace App\Modules\Matching\Presentation\Http\Resources;

use App\Modules\Matching\Domain\Entities\PetMatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PetMatch
 */
final class MatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PetMatch $match */
        $match = $this->resource;

        return [
            'id' => $match->id()->toString(),
            'pet_a_id' => $match->petAId()->toString(),
            'pet_b_id' => $match->petBId()->toString(),
            'matched_at' => $match->matchedAt()->format(DATE_ATOM),
        ];
    }
}
