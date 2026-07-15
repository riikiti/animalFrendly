<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Infrastructure\Listeners;

use App\Modules\Marketplace\Application\Contracts\CommissionRateResolverInterface;
use App\Modules\Marketplace\Domain\Enums\OrderStatus;
use App\Modules\Marketplace\Domain\Repositories\ListingRepositoryInterface;
use App\Modules\Marketplace\Domain\Repositories\OrderRepositoryInterface;
use App\Modules\Payment\Domain\Events\PaymentSucceeded;
use Illuminate\Support\Facades\DB;

final class MarkOrderPaidOnPaymentSucceeded
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly ListingRepositoryInterface $listings,
        private readonly CommissionRateResolverInterface $commissionRates,
    ) {}

    public function handle(PaymentSucceeded $event): void
    {
        if ($event->payableType !== 'order') {
            return;
        }

        $order = $this->orders->findById($event->payableId);

        // Вебхук может прийти повторно — идемпотентность на уровне итогового состояния,
        // см. docs/rules/04-payments-escrow.md.
        if ($order === null || $order->status() !== OrderStatus::PendingPayment) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $bps = $this->commissionRates->basisPointsFor($order->sellerId());
            $commission = $order->amount()->percentage($bps);
            $order->markPaid($commission, (int) config('yookassa.escrow_hold_days'));
            $this->orders->save($order, null, 'payment_succeeded');

            $listing = $this->listings->findById($order->listingId());

            if ($listing !== null) {
                $listing->markSold();
                $this->listings->save($listing);
            }
        });
    }
}
