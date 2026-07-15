<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Application\Services;

use App\Modules\Subscription\Domain\Entities\SubscriptionPlan;
use App\Modules\Subscription\Domain\Enums\FeatureKey;
use App\Modules\Subscription\Domain\Repositories\FeatureUsageRepositoryInterface;
use App\Modules\Subscription\Domain\Repositories\SubscriptionPlanRepositoryInterface;
use App\Modules\Subscription\Domain\Repositories\SubscriptionRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

/**
 * Единая точка проверки доступа к фиче и расчёта комиссии — см. docs/plan/09-flow-subscriptions.md.
 * Другие модули не обращаются к этому классу напрямую (Application-слой Subscription — приватный
 * для модуля), а только через тонкие адаптеры, реализующие их собственные контракты
 * (Matching\...\SubscriptionFeatureGateInterface, Marketplace\...\CommissionRateResolverInterface).
 */
final class SubscriptionFeatureGate
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptions,
        private readonly SubscriptionPlanRepositoryInterface $plans,
        private readonly FeatureUsageRepositoryInterface $usage,
    ) {}

    public function consume(Id $userId, FeatureKey $featureKey): bool
    {
        $plan = $this->resolveActivePlan($userId);

        // Тарифы ещё не засеяны (например, на свежем окружении) — не блокируем действие
        // из-за отсутствующей конфигурации, лимит просто не применяется.
        if ($plan === null) {
            return true;
        }

        $limit = $plan->limitFor($featureKey);
        $periodStart = $featureKey->periodStartFor(new DateTimeImmutable);

        return $this->usage->tryConsume($userId, $featureKey, $periodStart, $limit);
    }

    public function commissionBasisPointsFor(Id $userId): int
    {
        return $this->resolveActivePlan($userId)?->marketplaceCommissionBps()
            ?? (int) config('yookassa.commission_basis_points');
    }

    private function resolveActivePlan(Id $userId): ?SubscriptionPlan
    {
        $subscription = $this->subscriptions->findActiveForUser($userId);

        return $subscription !== null
            ? $this->plans->findById($subscription->planId())
            : $this->plans->findBySlug('free');
    }
}
