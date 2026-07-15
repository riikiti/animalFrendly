<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Infrastructure\Console;

use App\Modules\Subscription\Application\Contracts\SubscriptionBillingGatewayInterface;
use App\Modules\Subscription\Domain\Enums\SubscriptionStatus;
use App\Modules\Subscription\Domain\Repositories\SubscriptionPlanRepositoryInterface;
use App\Modules\Subscription\Domain\Repositories\SubscriptionRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use DateInterval;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Суточная джоба биллинга подписок: автосписание за истёкший период, завершение доступа при
 * отменённом автопродлении, и разбор past_due (повторная попытка списания в течение
 * grace-периода, иначе — expire). Паттерн 1:1 с
 * Marketplace\Infrastructure\Console\AutoConfirmEscrowDealsCommand.
 */
final class ProcessSubscriptionBillingCommand extends Command
{
    protected $signature = 'subscriptions:process-billing';

    protected $description = 'Автосписание за истёкшие периоды подписки, разбор past_due и завершение отменённых подписок';

    public function handle(
        SubscriptionRepositoryInterface $subscriptions,
        SubscriptionPlanRepositoryInterface $plans,
        SubscriptionBillingGatewayInterface $billingGateway,
    ): int {
        $now = new DateTimeImmutable;
        $charged = 0;
        $expired = 0;

        foreach ($subscriptions->findDueForBilling($now) as $due) {
            $this->withLock($due->id(), function () use ($subscriptions, $plans, $billingGateway, $due, $now, &$charged): void {
                $fresh = $subscriptions->findById($due->id());

                if ($fresh === null || $fresh->status() !== SubscriptionStatus::Active || ! $fresh->autoRenew()) {
                    return;
                }

                $periodEndsAt = $fresh->currentPeriodEndsAt();

                if ($periodEndsAt === null || $periodEndsAt > $now) {
                    return;
                }

                $plan = $plans->findById($fresh->planId());
                $paymentMethodId = $fresh->yookassaPaymentMethodId();

                if ($plan === null || $paymentMethodId === null) {
                    return;
                }

                $billingGateway->chargeRecurring($fresh->id(), $paymentMethodId, $plan->price(), $periodEndsAt->format('Y-m'));
                $charged++;
            });
        }

        foreach ($subscriptions->findEndedWithoutRenewal($now) as $ended) {
            $this->withLock($ended->id(), function () use ($subscriptions, $ended, $now, &$expired): void {
                $fresh = $subscriptions->findById($ended->id());

                if ($fresh === null || $fresh->status() !== SubscriptionStatus::Active || $fresh->autoRenew()) {
                    return;
                }

                $periodEndsAt = $fresh->currentPeriodEndsAt();

                if ($periodEndsAt === null || $periodEndsAt > $now) {
                    return;
                }

                $fresh->expire();
                $subscriptions->save($fresh);
                $expired++;
            });
        }

        $graceDays = (int) config('subscription.past_due_grace_days');
        $cutoff = $now->sub(new DateInterval("P{$graceDays}D"));

        foreach ($subscriptions->findPastDue() as $pastDue) {
            $this->withLock($pastDue->id(), function () use ($subscriptions, $plans, $billingGateway, $pastDue, $cutoff, &$expired, &$charged): void {
                $fresh = $subscriptions->findById($pastDue->id());

                if ($fresh === null || $fresh->status() !== SubscriptionStatus::PastDue) {
                    return;
                }

                $periodEndsAt = $fresh->currentPeriodEndsAt();

                if ($periodEndsAt !== null && $periodEndsAt <= $cutoff) {
                    $fresh->expire();
                    $subscriptions->save($fresh);
                    $expired++;

                    return;
                }

                $plan = $plans->findById($fresh->planId());
                $paymentMethodId = $fresh->yookassaPaymentMethodId();

                if ($plan === null || $paymentMethodId === null || $periodEndsAt === null) {
                    return;
                }

                $billingGateway->chargeRecurring($fresh->id(), $paymentMethodId, $plan->price(), $periodEndsAt->format('Y-m'));
                $charged++;
            });
        }

        $this->info("Списаний поставлено: {$charged}. Подписок завершено: {$expired}.");

        return self::SUCCESS;
    }

    private function withLock(Id $subscriptionId, callable $callback): void
    {
        Cache::lock("subscription:{$subscriptionId->toString()}", 10)->block(5, $callback);
    }
}
