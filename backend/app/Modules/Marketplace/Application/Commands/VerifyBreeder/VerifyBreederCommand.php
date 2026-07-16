<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\VerifyBreeder;

final class VerifyBreederCommand
{
    public function __construct(
        public readonly string $breederId,
        public readonly string $moderatorUserId,
        public readonly bool $approve,
    ) {}
}
