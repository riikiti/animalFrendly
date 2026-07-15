<?php

declare(strict_types=1);

use App\Modules\Chat\Application\Services\ConversationAccessGuard;
use App\Modules\Chat\Domain\Exceptions\ConversationAccessDeniedException;
use App\Modules\Chat\Domain\Repositories\ConversationRepositoryInterface;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Support\Facades\Broadcast;

/**
 * Переиспользует ConversationRepositoryInterface/ConversationAccessGuard — ту же проверку
 * участника, что уже делают SendMessageHandler/GetConversationForMatchHandler. $user
 * резолвится тем же Sanctum-guard'ом, что и обычные API-запросы (см. bootstrap/app.php —
 * middleware auth:sanctum на /api/broadcasting/auth), отдельного "сокет-логина" нет.
 */
Broadcast::channel('conversation.{conversationId}', function (User $user, string $conversationId): bool {
    $conversation = app(ConversationRepositoryInterface::class)->findById(Id::fromString($conversationId));

    if ($conversation === null) {
        return false;
    }

    try {
        app(ConversationAccessGuard::class)->assertParticipant($conversation, Id::fromString($user->id));

        return true;
    } catch (ConversationAccessDeniedException) {
        return false;
    }
});
