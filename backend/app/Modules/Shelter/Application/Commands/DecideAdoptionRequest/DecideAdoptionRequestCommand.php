<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Commands\DecideAdoptionRequest;

final class DecideAdoptionRequestCommand
{
    public function __construct(
        public readonly string $adoptionRequestId,
        public readonly string $actingUserId,
        public readonly bool $approve,
    ) {}
}
