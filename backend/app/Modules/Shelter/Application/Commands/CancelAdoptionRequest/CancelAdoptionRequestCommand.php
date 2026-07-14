<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Commands\CancelAdoptionRequest;

final class CancelAdoptionRequestCommand
{
    public function __construct(
        public readonly string $adoptionRequestId,
        public readonly string $actingUserId,
    ) {}
}
