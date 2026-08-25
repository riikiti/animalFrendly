<?php

declare(strict_types=1);

namespace App\Modules\Shop\Presentation\Http\Resources;

use App\Modules\Shop\Domain\Entities\ShopOrder;
use App\Modules\Shop\Domain\Entities\ShopOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShopOrder
 */
final class ShopOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ShopOrder $order */
        $order = $this->resource;

        return [
            'id' => $order->id()->toString(),
            // Заказы одного оформления оплачены вместе — по нему их можно сгруппировать.
            'checkout_id' => $order->checkoutId()->toString(),
            'buyer_id' => $order->buyerId()->toString(),
            'seller_id' => $order->sellerId()->toString(),
            'status' => $order->status()->value,
            'items' => array_map(static fn (ShopOrderItem $item): array => [
                'product_id' => $item->productId()->toString(),
                'title' => $item->title(),
                'price_amount' => $item->price()->minorUnits,
                'quantity' => $item->quantity(),
            ], $order->items()),
            'items_amount' => $order->itemsAmount()->minorUnits,
            'delivery_amount' => $order->deliveryAmount()->minorUnits,
            'amount' => $order->amount()->minorUnits,
            'commission_amount' => $order->commissionAmount()?->minorUnits,
            'payout_amount' => $order->payoutAmount()?->minorUnits,
            'currency' => $order->amount()->currency,
            'delivery_method' => $order->deliveryMethod()->value,
            'delivery_label' => $order->deliveryMethod()->label(),
            'delivery_address' => $order->deliveryAddress(),
            'escrow_hold_until' => $order->escrowHoldUntil()?->format(DATE_ATOM),
            'buyer_confirmed_at' => $order->buyerConfirmedAt()?->format(DATE_ATOM),
            'seller_confirmed_at' => $order->sellerConfirmedAt()?->format(DATE_ATOM),
        ];
    }
}
