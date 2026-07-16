<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Infrastructure\Adapters;

use App\Modules\Profile\Application\Contracts\SubscriptionFeatureGateInterface;
use App\Modules\Subscription\Domain\Repositories\SubscriptionRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

/**
 * Единственное место, где модуль Subscription "знает" про Profile — тонкий адаптер контракта,
 * объявленного в Profile\Application\Contracts\SubscriptionFeatureGateInterface. Байндится в
 * SubscriptionServiceProvider. В отличие от MatchingFeatureGateAdapter (периодические квоты
 * через SubscriptionFeatureGate/FeatureUsageRepository), лимит анкет — статичный признак
 * тарифа, поэтому напрямую проверяет наличие активной подписки, без учёта расхода за период.
 */
final class ProfileFeatureGateAdapter implements SubscriptionFeatureGateInterface
{
    public function __construct(private readonly SubscriptionRepositoryInterface $subscriptions) {}

    public function hasUnlimitedPets(Id $userId): bool
    {
        return $this->subscriptions->findActiveForUser($userId) !== null;
    }
}
