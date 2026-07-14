<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Commands\VerifyShelter;

final class VerifyShelterCommand
{
    public function __construct(
        public readonly string $shelterId,
        public readonly string $moderatorUserId,
        public readonly bool $approve,
    ) {}
}
