<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Commands\CreateConversationForShelterContact;

final class CreateConversationForShelterContactCommand
{
    public function __construct(
        public readonly string $shelterId,
        public readonly string $initiatorUserId,
        public readonly ?string $shelterAnimalId = null,
    ) {}
}
