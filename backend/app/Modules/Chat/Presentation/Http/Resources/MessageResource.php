<?php

declare(strict_types=1);

namespace App\Modules\Chat\Presentation\Http\Resources;

use App\Modules\Chat\Domain\Entities\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Message
 */
final class MessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Message $message */
        $message = $this->resource;

        return [
            'id' => $message->id()->toString(),
            'conversation_id' => $message->conversationId()->toString(),
            'sender_id' => $message->senderId()->toString(),
            'body' => $message->body(),
            'read_at' => $message->readAt()?->format(DATE_ATOM),
            'created_at' => $message->createdAt()->format(DATE_ATOM),
        ];
    }
}
