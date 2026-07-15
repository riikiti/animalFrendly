<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Presentation\Http\Resources;

use App\Modules\Marketplace\Domain\Entities\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Принимает либо Order напрямую (index/purchase/confirm/cancel — без раскрытия локации
 * контрагента), либо array{order: Order, counterpart_address: ?string, counterpart_location:
 * ?array{lat: float, lng: float}} — так отдаёт OrderController::show() уже после оплаты, см.
 * ListingResource для того же приёма.
 */
final class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $order = $this->resource instanceof Order ? $this->resource : $this->resource['order'];
        $counterpartAddress = is_array($this->resource) ? $this->resource['counterpart_address'] : null;
        $counterpartLocation = is_array($this->resource) ? $this->resource['counterpart_location'] : null;

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
            'counterpart_address' => $counterpartAddress,
            'counterpart_location' => $counterpartLocation,
        ];
    }
}
