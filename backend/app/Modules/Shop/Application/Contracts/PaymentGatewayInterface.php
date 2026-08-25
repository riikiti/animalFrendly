<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\Contracts;

use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

/**
 * Контракт в сторону модуля Payment — объявлен здесь (в Shop), реализуется в
 * Payment\Infrastructure\Adapters\ShopPaymentGateway и байндится в PaymentServiceProvider.
 * Тот же приём, что у Marketplace. См. docs/rules/01-backend.md.
 */
interface PaymentGatewayInterface
{
    public function initiate(Id $checkoutId, Money $amount, string $returnUrl): PaymentInitiationResult;
}
