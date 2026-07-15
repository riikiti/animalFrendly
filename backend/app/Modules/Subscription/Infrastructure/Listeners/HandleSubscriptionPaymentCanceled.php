<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Infrastructure\Listeners;

use App\Modules\Payment\Domain\Events\PaymentCanceled;
use App\Modules\Subscription\Domain\Enums\SubscriptionStatus;
use App\Modules\Subscription\Domain\Repositories\SubscriptionRepositoryInterface;

/**
 * Первая оплата не прошла → подписка так и не активировалась (expire); отказ автосписания у
 * уже активной подписки → past_due с grace-периодом (см. ProcessSubscriptionBillingCommand).
 */
final class HandleSubscriptionPaymentCanceled
{
    public function __construct(private readonly SubscriptionRepositoryInterface $subscriptions) {}

    public function handle(PaymentCanceled $event): void
    {
        if ($event->payableType !== 'subscription') {
            return;
        }

        $subscription = $this->subscriptions->findById($event->payableId);

        if ($subscription === null) {
            return;
        }

        if ($subscription->status() === SubscriptionStatus::PendingPayment) {
            $subscription->expire();
        } elseif ($subscription->status() === SubscriptionStatus::Active) {
            $subscription->markPastDue();
        } else {
            return;
        }

        $this->subscriptions->save($subscription);
    }
}
