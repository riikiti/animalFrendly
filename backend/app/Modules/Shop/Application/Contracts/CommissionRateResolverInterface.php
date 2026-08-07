<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\Contracts;

use App\Shared\Domain\ValueObjects\Id;

/**
 * Контракт в сторону модуля Subscription — объявлен здесь (в Shop), реализуется в
 * Subscription\Infrastructure\Adapters\ShopCommissionRateResolver. Комиссия магазина
 * считается по тому же тарифу продавца, что и в маркетплейсе.
 */
interface CommissionRateResolverInterface
{
    /**
     * Комиссия площадки в базисных пунктах (500 = 5.00%).
     */
    public function basisPointsFor(Id $sellerId): int;
}
