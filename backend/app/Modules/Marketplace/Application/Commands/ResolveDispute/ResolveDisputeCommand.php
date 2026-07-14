<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\ResolveDispute;

final class ResolveDisputeCommand
{
    public function __construct(
        public readonly string $disputeId,
        public readonly string $resolvedBy,
        public readonly string $resolution,
    ) {}
}
