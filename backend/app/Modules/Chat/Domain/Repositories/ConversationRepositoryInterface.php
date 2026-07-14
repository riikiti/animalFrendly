<?php

declare(strict_types=1);

namespace App\Modules\Chat\Domain\Repositories;

use App\Modules\Chat\Domain\Entities\Conversation;
use App\Shared\Domain\ValueObjects\Id;

interface ConversationRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(Conversation $conversation): void;

    public function findById(Id $id): ?Conversation;

    public function findByMatchId(Id $matchId): ?Conversation;

    /**
     * @param  list<Id>  $matchIds
     * @return list<Conversation>
     */
    public function findByMatchIds(array $matchIds): array;
}
