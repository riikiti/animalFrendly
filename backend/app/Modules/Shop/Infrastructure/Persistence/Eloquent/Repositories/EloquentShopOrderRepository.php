<?php

declare(strict_types=1);

namespace App\Modules\Shop\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Shop\Domain\Entities\ShopOrder;
use App\Modules\Shop\Domain\Entities\ShopOrderItem;
use App\Modules\Shop\Domain\Enums\DeliveryMethod;
use App\Modules\Shop\Domain\Enums\ShopOrderStatus;
use App\Modules\Shop\Domain\Repositories\ShopOrderRepositoryInterface;
use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Models\ShopCheckoutModel;
use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Models\ShopOrderItemModel;
use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Models\ShopOrderModel;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Carbon;

final class EloquentShopOrderRepository implements ShopOrderRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function startCheckout(Id $buyerId): Id
    {
        $id = Id::generate();

        ShopCheckoutModel::query()->create([
            'id' => $id->toString(),
            'buyer_id' => $buyerId->toString(),
            'amount' => 0,
        ]);

        return $id;
    }

    public function setCheckoutAmount(Id $checkoutId, Money $amount): void
    {
        ShopCheckoutModel::query()
            ->whereKey($checkoutId->toString())
            ->update(['amount' => $amount->minorUnits, 'currency' => $amount->currency]);
    }

    public function save(ShopOrder $order): void
    {
        $model = ShopOrderModel::query()->updateOrCreate(
            ['id' => $order->id()->toString()],
            [
                'checkout_id' => $order->checkoutId()->toString(),
                'buyer_id' => $order->buyerId()->toString(),
                'seller_id' => $order->sellerId()->toString(),
                'status' => $order->status()->value,
                'items_amount' => $order->itemsAmount()->minorUnits,
                'delivery_amount' => $order->deliveryAmount()->minorUnits,
                'amount' => $order->amount()->minorUnits,
                'commission_amount' => $order->commissionAmount()?->minorUnits,
                'payout_amount' => $order->payoutAmount()?->minorUnits,
                'currency' => $order->amount()->currency,
                'delivery_method' => $order->deliveryMethod()->value,
                'delivery_address' => $order->deliveryAddress(),
                'escrow_hold_until' => $order->escrowHoldUntil(),
                'buyer_confirmed_at' => $order->buyerConfirmedAt(),
                'seller_confirmed_at' => $order->sellerConfirmedAt(),
            ],
        );

        // Позиции пишем один раз при создании заказа — потом состав не меняется.
        if ($model->items()->count() === 0) {
            foreach ($order->items() as $item) {
                ShopOrderItemModel::query()->create([
                    'id' => $item->id()->toString(),
                    'order_id' => $order->id()->toString(),
                    'product_id' => $item->productId()->toString(),
                    'title' => $item->title(),
                    'price_amount' => $item->price()->minorUnits,
                    'quantity' => $item->quantity(),
                ]);
            }
        }
    }

    public function findById(Id $id): ?ShopOrder
    {
        $model = ShopOrderModel::query()->with('items')->find($id->toString());

        return $model === null ? null : $this->toDomain($model);
    }

    public function listFor(Id $userId, string $role): array
    {
        $column = $role === 'seller' ? 'seller_id' : 'buyer_id';

        return ShopOrderModel::query()
            ->with('items')
            ->where($column, $userId->toString())
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ShopOrderModel $model): ShopOrder => $this->toDomain($model))
            ->all();
    }

    public function listByCheckout(Id $checkoutId): array
    {
        return ShopOrderModel::query()
            ->with('items')
            ->where('checkout_id', $checkoutId->toString())
            ->get()
            ->map(fn (ShopOrderModel $model): ShopOrder => $this->toDomain($model))
            ->all();
    }

    public function listExpiredEscrow(): array
    {
        return ShopOrderModel::query()
            ->with('items')
            ->whereIn('status', [ShopOrderStatus::PaidEscrow->value, ShopOrderStatus::Shipped->value])
            ->whereNotNull('escrow_hold_until')
            ->where('escrow_hold_until', '<=', Carbon::now())
            ->get()
            ->map(fn (ShopOrderModel $model): ShopOrder => $this->toDomain($model))
            ->all();
    }

    private function toDomain(ShopOrderModel $model): ShopOrder
    {
        $currency = $model->currency;

        $items = $model->items
            ->map(static fn (ShopOrderItemModel $item): ShopOrderItem => new ShopOrderItem(
                Id::fromString($item->id),
                Id::fromString($item->product_id),
                $item->title,
                Money::fromMinorUnits($item->price_amount, $currency),
                $item->quantity,
            ))
            ->all();

        return ShopOrder::reconstitute(
            Id::fromString($model->id),
            Id::fromString($model->checkout_id),
            Id::fromString($model->buyer_id),
            Id::fromString($model->seller_id),
            $items,
            Money::fromMinorUnits($model->items_amount, $currency),
            Money::fromMinorUnits($model->delivery_amount, $currency),
            Money::fromMinorUnits($model->amount, $currency),
            DeliveryMethod::from($model->delivery_method),
            $model->delivery_address,
            ShopOrderStatus::from($model->status),
            $model->commission_amount === null ? null : Money::fromMinorUnits($model->commission_amount, $currency),
            $model->payout_amount === null ? null : Money::fromMinorUnits($model->payout_amount, $currency),
            $this->toDate($model->escrow_hold_until),
            $this->toDate($model->buyer_confirmed_at),
            $this->toDate($model->seller_confirmed_at),
        );
    }

    private function toDate(?DateTimeInterface $value): ?DateTimeImmutable
    {
        return $value === null ? null : DateTimeImmutable::createFromInterface($value);
    }
}
