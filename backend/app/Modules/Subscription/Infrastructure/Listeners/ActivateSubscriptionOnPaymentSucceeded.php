<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Infrastructure\Listeners;

use App\Modules\Payment\Domain\Events\PaymentSucceeded;
use App\Modules\Subscription\Domain\Enums\BillingPeriod;
use App\Modules\Subscription\Domain\Enums\SubscriptionStatus;
use App\Modules\Subscription\Domain\Repositories\SubscriptionPlanRepositoryInterface;
use App\Modules\Subscription\Domain\Repositories\SubscriptionRepositoryInterface;
use DateInterval;
use DateTimeImmutable;

/**
 * Активирует подписку по вебхуку ЮKassa payment.succeeded — первая оплата или повторная после
 * past_due. Идемпотентно: подписка, уже не в pending_payment/past_due, игнорируется (вебхук
 * может прийти повторно), см. docs/rules/04-payments-escrow.md.
 */
final class ActivateSubscriptionOnPaymentSucceeded
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptions,
        private readonly SubscriptionPlanRepositoryInterface $plans,
    ) {}

    public function handle(PaymentSucceeded $event): void
    {
        if ($event->payableType !== 'subscription') {
            return;
        }

        $subscription = $this->subscriptions->findById($event->payableId);

        if ($subscription === null || ! in_array($subscription->status(), [
            SubscriptionStatus::PendingPayment,
            SubscriptionStatus::PastDue,
        ], true)) {
            return;
        }

        $plan = $this->plans->findById($subscription->planId());

        if ($plan === null) {
            return;
        }

        $paymentMethod = $event->rawPayload['payment_method'] ?? null;
        $paymentMethodId = is_array($paymentMethod) && ($paymentMethod['saved'] ?? false) === true
            ? $paymentMethod['id'] ?? null
            : null;

        $subscription->activate(
            $this->calculatePeriodEnd($plan->period()),
            is_string($paymentMethodId) ? $paymentMethodId : null,
        );

        $this->subscriptions->save($subscription);
    }

    private function calculatePeriodEnd(BillingPeriod $period): DateTimeImmutable
    {
        return match ($period) {
            BillingPeriod::Month => (new DateTimeImmutable)->add(new DateInterval('P1M')),
            BillingPeriod::Year => (new DateTimeImmutable)->add(new DateInterval('P1Y')),
        };
    }
}
