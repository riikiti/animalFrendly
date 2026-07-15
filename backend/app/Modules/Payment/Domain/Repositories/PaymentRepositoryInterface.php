<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Repositories;

use App\Modules\Payment\Domain\Entities\Payment;
use App\Shared\Domain\ValueObjects\Id;

interface PaymentRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(Payment $payment): void;

    public function findById(Id $id): ?Payment;

    public function findByYookassaId(string $yookassaPaymentId): ?Payment;

    public function findByPayable(string $payableType, Id $payableId): ?Payment;

    public function findByIdempotencyKey(string $idempotencyKey): ?Payment;
}
