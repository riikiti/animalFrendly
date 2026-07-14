<?php

declare(strict_types=1);

namespace App\Modules\Chat\Domain\Repositories;

use App\Modules\Chat\Domain\Entities\Message;
use App\Shared\Domain\ValueObjects\Id;

interface MessageRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(Message $message): void;

    /**
     * @return list<Message>
     */
    public function findByConversation(Id $conversationId, int $limit = 50): array;
}
