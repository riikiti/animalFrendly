<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Infrastructure\Adapters;

use App\Modules\Marketplace\Application\Contracts\CommissionRateResolverInterface;
use App\Modules\Subscription\Application\Services\SubscriptionFeatureGate;
use App\Shared\Domain\ValueObjects\Id;

/**
 * Единственное место, где модуль Subscription "знает" про Marketplace — тонкий адаптер
 * контракта, объявленного в Marketplace\Application\Contracts\CommissionRateResolverInterface.
 * Байндится в SubscriptionServiceProvider.
 */
final class MarketplaceCommissionRateResolver implements CommissionRateResolverInterface
{
    public function __construct(private readonly SubscriptionFeatureGate $featureGate) {}

    public function basisPointsFor(Id $sellerId): int
    {
        return $this->featureGate->commissionBasisPointsFor($sellerId);
    }
}
