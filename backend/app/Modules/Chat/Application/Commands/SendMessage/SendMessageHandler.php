<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Commands\SendMessage;

use App\Modules\Chat\Application\Services\ConversationAccessGuard;
use App\Modules\Chat\Domain\Entities\Message;
use App\Modules\Chat\Domain\Events\MessageSent;
use App\Modules\Chat\Domain\Exceptions\ConversationNotFoundException;
use App\Modules\Chat\Domain\Repositories\ConversationRepositoryInterface;
use App\Modules\Chat\Domain\Repositories\MessageRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Contracts\Events\Dispatcher;

final class SendMessageHandler
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversations,
        private readonly MessageRepositoryInterface $messages,
        private readonly ConversationAccessGuard $accessGuard,
        private readonly Dispatcher $events,
    ) {}

    public function handle(SendMessageCommand $command): Message
    {
        $conversationId = Id::fromString($command->conversationId);
        $conversation = $this->conversations->findById($conversationId);

        if ($conversation === null) {
            throw ConversationNotFoundException::forId($command->conversationId);
        }

        $actingUserId = Id::fromString($command->actingUserId);
        $this->accessGuard->assertParticipant($conversation, $actingUserId);

        $message = Message::send($this->messages->nextIdentity(), $conversationId, $actingUserId, $command->body);
        $this->messages->save($message);

        $recipientId = $this->accessGuard->resolveRecipientId($conversation, $actingUserId);

        if ($recipientId !== null) {
            $this->events->dispatch(new MessageSent(
                conversationId: $conversationId,
                messageId: $message->id(),
                senderUserId: $actingUserId,
                recipientUserId: $recipientId,
                body: $message->body(),
                occurredAt: $message->createdAt(),
            ));
        }

        return $message;
    }
}
