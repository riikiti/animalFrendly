<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class PaymentNotFoundException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("Платёж {$id} не найден.");
    }

    public static function forYookassaId(string $yookassaPaymentId): self
    {
        return new self("Платёж с yookassa_payment_id={$yookassaPaymentId} не найден.");
    }
}
