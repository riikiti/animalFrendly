<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Commands\SubmitAdoptionRequest;

final class SubmitAdoptionRequestCommand
{
    public function __construct(
        public readonly string $shelterAnimalId,
        public readonly string $requesterUserId,
        public readonly ?string $message,
    ) {}
}
