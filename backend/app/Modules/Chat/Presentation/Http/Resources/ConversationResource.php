<?php

declare(strict_types=1);

namespace App\Modules\Chat\Presentation\Http\Resources;

use App\Modules\Chat\Domain\Entities\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Conversation
 */
final class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Conversation $conversation */
        $conversation = $this->resource;

        return [
            'id' => $conversation->id()->toString(),
            'match_id' => $conversation->matchId()?->toString(),
            'adoption_request_id' => $conversation->adoptionRequestId()?->toString(),
            'created_at' => $conversation->createdAt()->format(DATE_ATOM),
        ];
    }
}
