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

    public function findByAdoptionRequestId(Id $adoptionRequestId): ?Conversation;

    public function findByShelterAndInitiator(Id $shelterId, Id $initiatorUserId): ?Conversation;

    public function findByRecipientAndInitiator(Id $recipientUserId, Id $initiatorUserId): ?Conversation;

    /**
     * @param  list<Id>  $matchIds
     * @return list<Conversation>
     */
    public function findByMatchIds(array $matchIds): array;

    /**
     * @param  list<Id>  $adoptionRequestIds
     * @return list<Conversation>
     */
    public function findByAdoptionRequestIds(array $adoptionRequestIds): array;

    /**
     * Мои собственные исходящие прямые обращения — в приюты и к другим пользователям —
     * используется списком бесед.
     *
     * @return list<Conversation>
     */
    public function findByInitiatorUserId(Id $userId): array;

    /**
     * Входящие прямые обращения в приюты, которыми я владею.
     *
     * @param  list<Id>  $shelterIds
     * @return list<Conversation>
     */
    public function findByShelterIds(array $shelterIds): array;

    /**
     * Входящие прямые обращения ко мне лично (например, от покупателя на маркетплейсе).
     *
     * @return list<Conversation>
     */
    public function findByRecipientUserId(Id $userId): array;
}
