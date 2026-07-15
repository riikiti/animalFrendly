<?php

declare(strict_types=1);

namespace App\Modules\Matching\Application\Contracts;

use App\Shared\Domain\ValueObjects\Id;

/**
 * Контракт в сторону модуля Subscription — объявлен здесь (в Matching), реализуется в
 * Subscription\Infrastructure\Adapters\MatchingFeatureGateAdapter и байндится в
 * SubscriptionServiceProvider. Matching не знает про тарифы/биллинг — только про эту проверку.
 * См. docs/rules/01-backend.md и Marketplace\...\PaymentGatewayInterface — тот же паттерн.
 */
interface SubscriptionFeatureGateInterface
{
    /**
     * Пытается списать единицу лимита фичи для пользователя. Возвращает false, если лимит
     * тарифа исчерпан за текущий период.
     */
    public function consume(Id $userId, string $featureKey): bool;
}
