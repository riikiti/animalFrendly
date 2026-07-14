<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Commands\CreateConversationForAdoptionRequest;

final class CreateConversationForAdoptionRequestCommand
{
    public function __construct(public readonly string $adoptionRequestId) {}
}
