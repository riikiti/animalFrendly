<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Queries\GetConversationForAdoptionRequest;

use App\Modules\Chat\Application\Services\AdoptionRequestParticipantGuard;
use App\Modules\Chat\Domain\Entities\Conversation;
use App\Modules\Chat\Domain\Repositories\ConversationRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class GetConversationForAdoptionRequestHandler
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversations,
        private readonly AdoptionRequestParticipantGuard $participantGuard,
    ) {}

    public function handle(GetConversationForAdoptionRequestQuery $query): Conversation
    {
        $adoptionRequestId = Id::fromString($query->adoptionRequestId);
        $this->participantGuard->assertParticipant($adoptionRequestId, Id::fromString($query->actingUserId));

        $conversation = $this->conversations->findByAdoptionRequestId($adoptionRequestId);

        if ($conversation !== null) {
            return $conversation;
        }

        // Обычно беседа уже создана listener'ом на AdoptionRequestApproved — подстраховка
        // на случай гонки/ретрая, см. GetConversationForMatchHandler для аналогичного случая.
        $conversation = Conversation::createForAdoptionRequest($this->conversations->nextIdentity(), $adoptionRequestId);
        $this->conversations->save($conversation);

        return $conversation;
    }
}
