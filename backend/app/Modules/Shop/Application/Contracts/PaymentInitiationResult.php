<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\Contracts;

final class PaymentInitiationResult
{
    public function __construct(
        public readonly string $confirmationUrl,
        public readonly string $yookassaPaymentId,
    ) {}
}
