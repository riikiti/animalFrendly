<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Contracts;

use App\Shared\Domain\ValueObjects\Id;

/**
 * Контракт в сторону модуля Subscription — объявлен здесь (в Marketplace), реализуется в
 * Subscription\Infrastructure\Adapters\MarketplaceCommissionRateResolver и байндится в
 * SubscriptionServiceProvider. См. docs/rules/01-backend.md и
 * Marketplace\...\PaymentGatewayInterface — тот же паттерн для тарифа комиссии.
 */
interface CommissionRateResolverInterface
{
    /**
     * Комиссия площадки в базисных пунктах (500 = 5.00%) для продавца, с учётом его тарифа
     * подписки (или дефолтного free, если активной подписки нет).
     */
    public function basisPointsFor(Id $sellerId): int;
}
