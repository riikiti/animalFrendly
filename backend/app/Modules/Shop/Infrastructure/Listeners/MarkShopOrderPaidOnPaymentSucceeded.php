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
        if ($event->payableType !== 'shop_checkout') {
            return;
        }

        // Один платёж покрывает все заказы оформления — по одному на продавца.
        $orders = $this->orders->listByCheckout($event->payableId);

        DB::transaction(function () use ($orders): void {
            foreach ($orders as $order) {
                // Вебхук может прийти повторно — идемпотентность по итоговому состоянию,
                // см. docs/rules/04-payments-escrow.md.
                if ($order->status() !== ShopOrderStatus::PendingPayment) {
                    continue;
                }

                $order->markPaid(
                    $this->commissionRates->basisPointsFor($order->sellerId()),
                    (int) config('yookassa.escrow_hold_days'),
                );
                $this->orders->save($order);
            }
        });
    }
}
