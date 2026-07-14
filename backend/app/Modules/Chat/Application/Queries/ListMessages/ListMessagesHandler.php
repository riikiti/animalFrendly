<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Queries\ListMessages;

use App\Modules\Chat\Application\Services\ConversationAccessGuard;
use App\Modules\Chat\Domain\Entities\Message;
use App\Modules\Chat\Domain\Exceptions\ConversationNotFoundException;
use App\Modules\Chat\Domain\Repositories\ConversationRepositoryInterface;
use App\Modules\Chat\Domain\Repositories\MessageRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class ListMessagesHandler
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversations,
        private readonly MessageRepositoryInterface $messages,
        private readonly ConversationAccessGuard $accessGuard,
    ) {}

    /**
     * @return list<Message>
     */
    public function handle(ListMessagesQuery $query): array
    {
        $conversationId = Id::fromString($query->conversationId);
        $conversation = $this->conversations->findById($conversationId);

        if ($conversation === null) {
            throw ConversationNotFoundException::forId($query->conversationId);
        }

        $this->accessGuard->assertParticipant($conversation, Id::fromString($query->actingUserId));

        return $this->messages->findByConversation($conversationId, $query->limit);
    }
}
