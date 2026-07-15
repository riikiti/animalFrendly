<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Payment\Domain\Entities\Payment as DomainPayment;
use App\Modules\Payment\Domain\Enums\PaymentStatus;
use App\Modules\Payment\Domain\Repositories\PaymentRepositoryInterface;
use App\Modules\Payment\Infrastructure\Persistence\Eloquent\Models\Payment as EloquentPayment;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

final class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainPayment $payment): void
    {
        EloquentPayment::query()->updateOrCreate(
            ['id' => $payment->id()->toString()],
            [
                'payable_type' => $payment->payableType(),
                'payable_id' => $payment->payableId()->toString(),
                'yookassa_payment_id' => $payment->yookassaPaymentId(),
                'idempotency_key' => $payment->idempotencyKey(),
                'amount' => $payment->amount()->minorUnits,
                'currency' => $payment->amount()->currency,
                'status' => $payment->status()->value,
                'raw_payload' => $payment->rawPayload(),
            ],
        );
    }

    public function findById(Id $id): ?DomainPayment
    {
        $model = EloquentPayment::query()->find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    public function findByYookassaId(string $yookassaPaymentId): ?DomainPayment
    {
        $model = EloquentPayment::query()->where('yookassa_payment_id', $yookassaPaymentId)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByPayable(string $payableType, Id $payableId): ?DomainPayment
    {
        $model = EloquentPayment::query()
            ->where('payable_type', $payableType)
            ->where('payable_id', $payableId->toString())
            ->orderByDesc('created_at')
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?DomainPayment
    {
        $model = EloquentPayment::query()->where('idempotency_key', $idempotencyKey)->first();

        return $model ? $this->toDomain($model) : null;
    }

    private function toDomain(EloquentPayment $model): DomainPayment
    {
        return DomainPayment::reconstitute(
            id: Id::fromString($model->id),
            payableType: $model->payable_type,
            payableId: Id::fromString($model->payable_id),
            idempotencyKey: $model->idempotency_key,
            amount: Money::fromMinorUnits((int) $model->amount, $model->currency),
            yookassaPaymentId: $model->yookassa_payment_id,
            status: PaymentStatus::from($model->status),
            rawPayload: $model->raw_payload,
        );
    }
}
