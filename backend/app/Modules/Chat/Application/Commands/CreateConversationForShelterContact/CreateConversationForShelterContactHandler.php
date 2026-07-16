<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Commands\CreateConversationForShelterContact;

use App\Modules\Chat\Domain\Entities\Conversation;
use App\Modules\Chat\Domain\Repositories\ConversationRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class CreateConversationForShelterContactHandler
{
    public function __construct(private readonly ConversationRepositoryInterface $conversations) {}

    public function handle(CreateConversationForShelterContactCommand $command): Conversation
    {
        $shelterId = Id::fromString($command->shelterId);
        $initiatorUserId = Id::fromString($command->initiatorUserId);

        $existing = $this->conversations->findByShelterAndInitiator($shelterId, $initiatorUserId);

        if ($existing !== null) {
            return $existing;
        }

        $conversation = Conversation::createForShelterContact(
            id: $this->conversations->nextIdentity(),
            shelterId: $shelterId,
            initiatorUserId: $initiatorUserId,
            shelterAnimalId: $command->shelterAnimalId !== null ? Id::fromString($command->shelterAnimalId) : null,
        );
        $this->conversations->save($conversation);

        return $conversation;
    }
}
