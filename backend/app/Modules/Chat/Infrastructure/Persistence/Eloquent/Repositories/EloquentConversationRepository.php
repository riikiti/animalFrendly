<?php

declare(strict_types=1);

namespace App\Modules\Chat\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Chat\Domain\Entities\Conversation as DomainConversation;
use App\Modules\Chat\Domain\Repositories\ConversationRepositoryInterface;
use App\Modules\Chat\Infrastructure\Persistence\Eloquent\Models\Conversation as EloquentConversation;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentConversationRepository implements ConversationRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainConversation $conversation): void
    {
        EloquentConversation::query()->updateOrCreate(
            ['id' => $conversation->id()->toString()],
            [
                'match_id' => $conversation->matchId()->toString(),
                'created_at' => $conversation->createdAt(),
            ],
        );
    }

    public function findById(Id $id): ?DomainConversation
    {
        $model = EloquentConversation::query()->find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    public function findByMatchId(Id $matchId): ?DomainConversation
    {
        $model = EloquentConversation::query()->where('match_id', $matchId->toString())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByMatchIds(array $matchIds): array
    {
        if ($matchIds === []) {
            return [];
        }

        $matchIdStrings = array_map(static fn (Id $id): string => $id->toString(), $matchIds);

        return array_values(
            EloquentConversation::query()
                ->whereIn('match_id', $matchIdStrings)
                ->orderByDesc('created_at')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    private function toDomain(EloquentConversation $model): DomainConversation
    {
        return DomainConversation::reconstitute(
            id: Id::fromString($model->id),
            matchId: Id::fromString($model->match_id),
            createdAt: $model->created_at->toDateTimeImmutable(),
        );
    }
}
