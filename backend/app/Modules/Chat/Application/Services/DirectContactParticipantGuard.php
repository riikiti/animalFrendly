<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Services;

use App\Modules\Chat\Domain\Entities\Conversation;
use App\Modules\Chat\Domain\Exceptions\ConversationAccessDeniedException;
use App\Shared\Domain\ValueObjects\Id;

/**
 * Самый простой из guard'ов — обе стороны (initiatorUserId/recipientUserId) уже лежат
 * прямо на Conversation, никакого поиска через репозиторий не требуется вообще (в отличие
 * от ShelterConversationParticipantGuard, которому нужно резолвить владельца приюта).
 */
final class DirectContactParticipantGuard
{
    public function assertParticipant(Conversation $conversation, Id $actingUserId): void
    {
        if ($conversation->initiatorUserId()?->equals($actingUserId) === true) {
            return;
        }

        if ($conversation->recipientUserId()?->equals($actingUserId) === true) {
            return;
        }

        throw ConversationAccessDeniedException::create();
    }

    public function otherParticipantId(Conversation $conversation, Id $senderId): ?Id
    {
        if ($conversation->initiatorUserId()?->equals($senderId) === true) {
            return $conversation->recipientUserId();
        }

        return $conversation->initiatorUserId();
    }
}
