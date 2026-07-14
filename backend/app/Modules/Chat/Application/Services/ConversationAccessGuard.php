<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Services;

use App\Modules\Chat\Domain\Entities\Conversation;
use App\Modules\Chat\Domain\Exceptions\ConversationAccessDeniedException;
use App\Shared\Domain\ValueObjects\Id;

final class ConversationAccessGuard
{
    public function __construct(
        private readonly MatchParticipantGuard $matchGuard,
        private readonly AdoptionRequestParticipantGuard $adoptionGuard,
    ) {}

    public function assertParticipant(Conversation $conversation, Id $actingUserId): void
    {
        if ($conversation->matchId() !== null) {
            $this->matchGuard->assertParticipant($conversation->matchId(), $actingUserId);

            return;
        }

        if ($conversation->adoptionRequestId() !== null) {
            $this->adoptionGuard->assertParticipant($conversation->adoptionRequestId(), $actingUserId);

            return;
        }

        throw ConversationAccessDeniedException::create();
    }
}
