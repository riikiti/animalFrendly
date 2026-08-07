<?php

declare(strict_types=1);

namespace App\Modules\Shop\Infrastructure\Listeners;

use App\Modules\Payment\Domain\Events\PaymentSucceeded;
use App\Modules\Shop\Application\Contracts\CommissionRateResolverInterface;
use App\Modules\Shop\Domain\Enums\ShopOrderStatus;
use App\Modules\Shop\Domain\Repositories\ShopOrderRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class MarkShopOrderPaidOnPaymentSucceeded
{
    public function __construct(
        private readonly ShopOrderRepositoryInterface $orders,
        private readonly CommissionRateResolverInterface $commissionRates,
    ) {}

    public function handle(PaymentSucceeded $event): void
    {
        if ($event->payableType !== 'shop_order') {
            return;
        }

        $order = $this->orders->findById($event->payableId);

        // Вебхук может прийти повторно — идемпотентность по итоговому состоянию,
        // см. docs/rules/04-payments-escrow.md.
        if ($order === null || $order->status() !== ShopOrderStatus::PendingPayment) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $order->markPaid(
                $this->commissionRates->basisPointsFor($order->sellerId()),
                (int) config('yookassa.escrow_hold_days'),
            );
            $this->orders->save($order);
        });
    }
}
