<?php

declare(strict_types=1);

namespace App\Modules\Chat\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Chat\Domain\Entities\Message as DomainMessage;
use App\Modules\Chat\Domain\Repositories\MessageRepositoryInterface;
use App\Modules\Chat\Infrastructure\Persistence\Eloquent\Models\Message as EloquentMessage;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentMessageRepository implements MessageRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainMessage $message): void
    {
        EloquentMessage::query()->updateOrCreate(
            ['id' => $message->id()->toString()],
            [
                'conversation_id' => $message->conversationId()->toString(),
                'sender_id' => $message->senderId()->toString(),
                'body' => $message->body(),
                'read_at' => $message->readAt(),
                'created_at' => $message->createdAt(),
            ],
        );
    }

    public function findByConversation(Id $conversationId, int $limit = 50): array
    {
        return array_values(
            EloquentMessage::query()
                ->where('conversation_id', $conversationId->toString())
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map($this->toDomain(...))
                ->reverse()
                ->all(),
        );
    }

    private function toDomain(EloquentMessage $model): DomainMessage
    {
        return DomainMessage::reconstitute(
            id: Id::fromString($model->id),
            conversationId: Id::fromString($model->conversation_id),
            senderId: Id::fromString($model->sender_id),
            body: $model->body,
            createdAt: $model->created_at->toDateTimeImmutable(),
            readAt: $model->read_at?->toDateTimeImmutable(),
        );
    }
}
