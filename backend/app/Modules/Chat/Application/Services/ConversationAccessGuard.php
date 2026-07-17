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
        private readonly ShelterConversationParticipantGuard $shelterGuard,
        private readonly DirectContactParticipantGuard $directGuard,
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

        if ($conversation->shelterId() !== null) {
            $this->shelterGuard->assertParticipant($conversation, $actingUserId);

            return;
        }

        if ($conversation->recipientUserId() !== null) {
            $this->directGuard->assertParticipant($conversation, $actingUserId);

            return;
        }

        throw ConversationAccessDeniedException::create();
    }

    /**
     * Возвращает id второго участника беседы (не отправителя) — используется модулем
     * Notification. Вызывается только после assertParticipant, поэтому $senderId гарантированно
     * участник.
     */
    public function resolveRecipientId(Conversation $conversation, Id $senderId): ?Id
    {
        if ($conversation->matchId() !== null) {
            $match = $this->matchGuard->assertParticipant($conversation->matchId(), $senderId);

            return $this->matchGuard->otherPetOwnerId($match, $senderId);
        }

        if ($conversation->adoptionRequestId() !== null) {
            $request = $this->adoptionGuard->assertParticipant($conversation->adoptionRequestId(), $senderId);

            return $this->adoptionGuard->otherParticipantId($request, $senderId);
        }

        if ($conversation->shelterId() !== null) {
            $this->shelterGuard->assertParticipant($conversation, $senderId);

            return $this->shelterGuard->otherParticipantId($conversation, $senderId);
        }

        if ($conversation->recipientUserId() !== null) {
            $this->directGuard->assertParticipant($conversation, $senderId);

            return $this->directGuard->otherParticipantId($conversation, $senderId);
        }

        return null;
    }
}
