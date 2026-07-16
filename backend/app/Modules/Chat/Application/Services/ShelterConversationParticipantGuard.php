<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Services;

use App\Modules\Chat\Domain\Entities\Conversation;
use App\Modules\Chat\Domain\Exceptions\ConversationAccessDeniedException;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

/**
 * В отличие от MatchParticipantGuard/AdoptionRequestParticipantGuard, у прямого обращения
 * в приют нет отдельной сущности-источника участников — все нужные id (shelterId,
 * initiatorUserId) уже лежат прямо на Conversation, поэтому guard принимает её саму, а не id.
 */
final class ShelterConversationParticipantGuard
{
    public function __construct(private readonly ShelterRepositoryInterface $shelters) {}

    public function assertParticipant(Conversation $conversation, Id $actingUserId): void
    {
        if ($conversation->initiatorUserId()?->equals($actingUserId) === true) {
            return;
        }

        if (! $this->isShelterOwner($conversation, $actingUserId)) {
            throw ConversationAccessDeniedException::create();
        }
    }

    public function otherParticipantId(Conversation $conversation, Id $senderId): ?Id
    {
        if ($conversation->initiatorUserId()?->equals($senderId) === true) {
            $shelter = $conversation->shelterId() !== null ? $this->shelters->findById($conversation->shelterId()) : null;

            return $shelter?->ownerUserId();
        }

        return $conversation->initiatorUserId();
    }

    private function isShelterOwner(Conversation $conversation, Id $actingUserId): bool
    {
        $shelter = $conversation->shelterId() !== null ? $this->shelters->findById($conversation->shelterId()) : null;

        return $shelter !== null && $shelter->ownerUserId()->equals($actingUserId);
    }
}
