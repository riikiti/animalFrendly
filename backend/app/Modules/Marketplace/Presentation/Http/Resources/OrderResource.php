<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Presentation\Http\Resources;

use App\Modules\Marketplace\Domain\Entities\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
final class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;

        return [
            'id' => $order->id()->toString(),
            'listing_id' => $order->listingId()->toString(),
            'buyer_id' => $order->buyerId()->toString(),
            'seller_id' => $order->sellerId()->toString(),
            'amount' => $order->amount()->minorUnits,
            'currency' => $order->amount()->currency,
            'commission_amount' => $order->commissionAmount()?->minorUnits,
            'payout_amount' => $order->payoutAmount()?->minorUnits,
            'status' => $order->status()->value,
            'buyer_confirmed_at' => $order->buyerConfirmedAt()?->format(DATE_ATOM),
            'seller_confirmed_at' => $order->sellerConfirmedAt()?->format(DATE_ATOM),
            'escrow_hold_until' => $order->escrowHoldUntil()?->format(DATE_ATOM),
        ];
    }
}
