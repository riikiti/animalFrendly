<?php

declare(strict_types=1);

namespace App\Modules\Chat\Presentation\Http\Resources;

use App\Modules\Chat\Domain\Entities\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Принимает либо Conversation напрямую (index() — список бесед, без раскрытия локации
 * контрагента), либо array{conversation: Conversation, counterpart_address: ?string,
 * counterpart_location: ?array{lat: float, lng: float}} — так отдают
 * conversationForMatch()/conversationForAdoptionRequest() уже после проверки участника, см.
 * OrderResource для того же приёма.
 */
final class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $conversation = $this->resource instanceof Conversation ? $this->resource : $this->resource['conversation'];
        $counterpartAddress = is_array($this->resource) ? $this->resource['counterpart_address'] : null;
        $counterpartLocation = is_array($this->resource) ? $this->resource['counterpart_location'] : null;

        return [
            'id' => $conversation->id()->toString(),
            'match_id' => $conversation->matchId()?->toString(),
            'adoption_request_id' => $conversation->adoptionRequestId()?->toString(),
            'created_at' => $conversation->createdAt()->format(DATE_ATOM),
            'counterpart_address' => $counterpartAddress,
            'counterpart_location' => $counterpartLocation,
        ];
    }
}
