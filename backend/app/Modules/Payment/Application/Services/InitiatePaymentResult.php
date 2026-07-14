<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Services;

final class InitiatePaymentResult
{
    public function __construct(
        public readonly string $confirmationUrl,
        public readonly string $yookassaPaymentId,
    ) {}
}
