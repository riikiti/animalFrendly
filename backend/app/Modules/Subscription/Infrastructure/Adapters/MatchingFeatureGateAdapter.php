<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Infrastructure\Adapters;

use App\Modules\Matching\Application\Contracts\SubscriptionFeatureGateInterface;
use App\Modules\Subscription\Application\Services\SubscriptionFeatureGate;
use App\Modules\Subscription\Domain\Enums\FeatureKey;
use App\Shared\Domain\ValueObjects\Id;

/**
 * Единственное место, где модуль Subscription "знает" про Matching — тонкий адаптер контракта,
 * объявленного в Matching\Application\Contracts\SubscriptionFeatureGateInterface. Байндится в
 * SubscriptionServiceProvider.
 */
final class MatchingFeatureGateAdapter implements SubscriptionFeatureGateInterface
{
    public function __construct(private readonly SubscriptionFeatureGate $featureGate) {}

    public function consume(Id $userId, string $featureKey): bool
    {
        return $this->featureGate->consume($userId, FeatureKey::from($featureKey));
    }
}
