<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\RegisterBreeder;

final class RegisterBreederCommand
{
    public function __construct(
        public readonly string $ownerUserId,
    ) {}
}
