<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Presentation\Http\Resources;

use App\Modules\Shelter\Domain\Entities\AdoptionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AdoptionRequest
 */
final class AdoptionRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AdoptionRequest $adoptionRequest */
        $adoptionRequest = $this->resource;

        return [
            'id' => $adoptionRequest->id()->toString(),
            'shelter_animal_id' => $adoptionRequest->shelterAnimalId()->toString(),
            'requester_user_id' => $adoptionRequest->requesterUserId()->toString(),
            'message' => $adoptionRequest->message(),
            'status' => $adoptionRequest->status()->value,
            'decided_at' => $adoptionRequest->decidedAt()?->format(DATE_ATOM),
        ];
    }
}
