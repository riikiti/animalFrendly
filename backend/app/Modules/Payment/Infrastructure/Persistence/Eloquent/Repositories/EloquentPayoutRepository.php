<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Payment\Domain\Entities\Payout as DomainPayout;
use App\Modules\Payment\Domain\Enums\PayoutStatus;
use App\Modules\Payment\Domain\Repositories\PayoutRepositoryInterface;
use App\Modules\Payment\Infrastructure\Persistence\Eloquent\Models\Payout as EloquentPayout;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

final class EloquentPayoutRepository implements PayoutRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainPayout $payout): void
    {
        EloquentPayout::query()->updateOrCreate(
            ['id' => $payout->id()->toString()],
            [
                'order_id' => $payout->orderId()->toString(),
                'seller_id' => $payout->sellerId()->toString(),
                'amount' => $payout->amount()->minorUnits,
                'status' => $payout->status()->value,
                'yookassa_payout_id' => $payout->yookassaPayoutId(),
                'processed_at' => $payout->processedAt(),
            ],
        );
    }

    public function findByOrderId(Id $orderId): ?DomainPayout
    {
        $model = EloquentPayout::query()->where('order_id', $orderId->toString())->first();

        return $model ? $this->toDomain($model) : null;
    }

    private function toDomain(EloquentPayout $model): DomainPayout
    {
        return DomainPayout::reconstitute(
            id: Id::fromString($model->id),
            orderId: Id::fromString($model->order_id),
            sellerId: Id::fromString($model->seller_id),
            amount: Money::fromMinorUnits((int) $model->amount, 'RUB'),
            status: PayoutStatus::from($model->status),
            yookassaPayoutId: $model->yookassa_payout_id,
            processedAt: $model->processed_at?->toDateTimeImmutable(),
        );
    }
}
