<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Infrastructure\Adapters;

use App\Modules\Shop\Application\Contracts\CommissionRateResolverInterface;
use App\Modules\Subscription\Application\Services\SubscriptionFeatureGate;
use App\Shared\Domain\ValueObjects\Id;

/**
 * Комиссия магазина считается по тому же тарифу продавца, что и в маркетплейсе —
 * адаптер контракта Shop\Application\Contracts\CommissionRateResolverInterface.
 */
final class ShopCommissionRateResolver implements CommissionRateResolverInterface
{
    public function __construct(private readonly SubscriptionFeatureGate $featureGate) {}

    public function basisPointsFor(Id $sellerId): int
    {
        return $this->featureGate->commissionBasisPointsFor($sellerId);
    }
}
