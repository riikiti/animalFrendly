<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Commands\CreateConversationForDirectContact;

use App\Modules\Chat\Domain\Entities\Conversation;
use App\Modules\Chat\Domain\Repositories\ConversationRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class CreateConversationForDirectContactHandler
{
    public function __construct(private readonly ConversationRepositoryInterface $conversations) {}

    public function handle(CreateConversationForDirectContactCommand $command): Conversation
    {
        $recipientUserId = Id::fromString($command->recipientUserId);
        $initiatorUserId = Id::fromString($command->initiatorUserId);

        $existing = $this->conversations->findByRecipientAndInitiator($recipientUserId, $initiatorUserId);

        if ($existing !== null) {
            return $existing;
        }

        $conversation = Conversation::createForDirectContact(
            id: $this->conversations->nextIdentity(),
            recipientUserId: $recipientUserId,
            initiatorUserId: $initiatorUserId,
        );
        $this->conversations->save($conversation);

        return $conversation;
    }
}
