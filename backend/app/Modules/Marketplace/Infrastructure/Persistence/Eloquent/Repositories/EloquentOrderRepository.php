<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Marketplace\Domain\Entities\Order as DomainOrder;
use App\Modules\Marketplace\Domain\Enums\OrderStatus;
use App\Modules\Marketplace\Domain\Repositories\OrderRepositoryInterface;
use App\Modules\Marketplace\Infrastructure\Persistence\Eloquent\Models\Order as EloquentOrder;
use App\Modules\Marketplace\Infrastructure\Persistence\Eloquent\Models\OrderStatusHistory;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainOrder $order, ?Id $actorUserId = null, ?string $reason = null): void
    {
        $previousStatus = EloquentOrder::query()
            ->where('id', $order->id()->toString())
            ->value('status');

        EloquentOrder::query()->updateOrCreate(
            ['id' => $order->id()->toString()],
            [
                'listing_id' => $order->listingId()->toString(),
                'buyer_id' => $order->buyerId()->toString(),
                'seller_id' => $order->sellerId()->toString(),
                'amount' => $order->amount()->minorUnits,
                'currency' => $order->amount()->currency,
                'commission_amount' => $order->commissionAmount()?->minorUnits,
                'payout_amount' => $order->payoutAmount()?->minorUnits,
                'status' => $order->status()->value,
                'buyer_confirmed_at' => $order->buyerConfirmedAt(),
                'seller_confirmed_at' => $order->sellerConfirmedAt(),
                'escrow_hold_until' => $order->escrowHoldUntil(),
            ],
        );

        $newStatus = $order->status()->value;

        if ($previousStatus !== $newStatus) {
            OrderStatusHistory::query()->create([
                'order_id' => $order->id()->toString(),
                'from_status' => $previousStatus,
                'to_status' => $newStatus,
                'actor_user_id' => $actorUserId?->toString(),
                'reason' => $reason,
            ]);
        }
    }

    public function findById(Id $id): ?DomainOrder
    {
        $model = EloquentOrder::query()->find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    public function findByBuyer(Id $buyerId): array
    {
        return array_values(
            EloquentOrder::query()
                ->where('buyer_id', $buyerId->toString())
                ->orderByDesc('created_at')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function findBySeller(Id $sellerId): array
    {
        return array_values(
            EloquentOrder::query()
                ->where('seller_id', $sellerId->toString())
                ->orderByDesc('created_at')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function findEscrowExpired(): array
    {
        return array_values(
            EloquentOrder::query()
                ->where('status', OrderStatus::PaidEscrow->value)
                ->where('escrow_hold_until', '<=', now())
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    private function toDomain(EloquentOrder $model): DomainOrder
    {
        return DomainOrder::reconstitute(
            id: Id::fromString($model->id),
            listingId: Id::fromString($model->listing_id),
            buyerId: Id::fromString($model->buyer_id),
            sellerId: Id::fromString($model->seller_id),
            amount: Money::fromMinorUnits((int) $model->amount, $model->currency),
            status: OrderStatus::from($model->status),
            commissionAmount: $model->commission_amount !== null
                ? Money::fromMinorUnits((int) $model->commission_amount, $model->currency)
                : null,
            payoutAmount: $model->payout_amount !== null
                ? Money::fromMinorUnits((int) $model->payout_amount, $model->currency)
                : null,
            buyerConfirmedAt: $model->buyer_confirmed_at?->toDateTimeImmutable(),
            sellerConfirmedAt: $model->seller_confirmed_at?->toDateTimeImmutable(),
            escrowHoldUntil: $model->escrow_hold_until?->toDateTimeImmutable(),
        );
    }
}
