<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Application\Contracts;

use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

/**
 * Контракт в сторону модуля Payment — объявлен здесь (в Subscription), реализуется в
 * Payment\Infrastructure\Adapters\SubscriptionBillingGateway и байндится в
 * PaymentServiceProvider. Subscription не знает про ЮKassa и HTTP — только про эти примитивы.
 * См. docs/rules/01-backend.md и Marketplace\Application\Contracts\PaymentGatewayInterface —
 * тот же паттерн, применённый для рекуррентного биллинга.
 */
interface SubscriptionBillingGatewayInterface
{
    /**
     * Первая оплата подписки — с сохранением способа оплаты для последующих автосписаний.
     */
    public function initiateFirstPayment(Id $subscriptionId, Money $amount, string $returnUrl): PaymentInitiationResult;

    /**
     * Списание за очередной период с ранее сохранённого способа оплаты — асинхронно
     * (ставит платёж в очередь), результат приходит тем же вебхук-потоком, что и обычная оплата.
     */
    public function chargeRecurring(Id $subscriptionId, string $paymentMethodId, Money $amount, string $periodLabel): void;
}
