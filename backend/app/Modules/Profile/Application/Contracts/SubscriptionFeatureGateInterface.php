<?php

declare(strict_types=1);

namespace App\Modules\Profile\Application\Contracts;

use App\Shared\Domain\ValueObjects\Id;

/**
 * Интерфейс объявлен в потребителе (Profile), реализация — в Subscription (см.
 * Subscription\Infrastructure\Adapters\ProfileFeatureGateAdapter), тот же паттерн, что
 * Matching\Application\Contracts\SubscriptionFeatureGateInterface.
 */
interface SubscriptionFeatureGateInterface
{
    /**
     * Статичный признак тарифа (не периодическая квота) — есть ли у пользователя прямо
     * сейчас активная подписка, снимающая лимит на количество анкет.
     */
    public function hasUnlimitedPets(Id $userId): bool;
}
