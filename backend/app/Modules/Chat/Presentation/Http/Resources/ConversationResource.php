<?php

declare(strict_types=1);

namespace App\Modules\Chat\Presentation\Http\Resources;

use App\Modules\Chat\Domain\Entities\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Принимает либо Conversation напрямую, либо array{conversation: Conversation,
 * counterpart_address?: ?string, counterpart_location?: ?array{lat: float, lng: float},
 * counterpart_name?: ?string, counterpart_avatar_url?: ?string} — так отдают
 * conversationForMatch()/conversationForAdoptionRequest()/conversationForShelter() (адрес +
 * локация, после проверки участника) и index() (только имя/аватар, без точной локации —
 * список бесед не раскрывает адрес), см. OrderResource для того же приёма.
 */
final class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $conversation = $this->resource instanceof Conversation ? $this->resource : $this->resource['conversation'];
        $counterpartAddress = is_array($this->resource) ? ($this->resource['counterpart_address'] ?? null) : null;
        $counterpartLocation = is_array($this->resource) ? ($this->resource['counterpart_location'] ?? null) : null;
        $counterpartName = is_array($this->resource) ? ($this->resource['counterpart_name'] ?? null) : null;
        $counterpartAvatarUrl = is_array($this->resource) ? ($this->resource['counterpart_avatar_url'] ?? null) : null;

        return [
            'id' => $conversation->id()->toString(),
            'match_id' => $conversation->matchId()?->toString(),
            'adoption_request_id' => $conversation->adoptionRequestId()?->toString(),
            'shelter_id' => $conversation->shelterId()?->toString(),
            'shelter_animal_id' => $conversation->shelterAnimalId()?->toString(),
            'created_at' => $conversation->createdAt()->format(DATE_ATOM),
            'counterpart_address' => $counterpartAddress,
            'counterpart_location' => $counterpartLocation,
            'counterpart_name' => $counterpartName,
            'counterpart_avatar_url' => $counterpartAvatarUrl,
        ];
    }
}
