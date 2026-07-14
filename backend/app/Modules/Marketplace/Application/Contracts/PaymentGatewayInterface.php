<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Contracts;

use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

/**
 * Контракт в сторону модуля Payment — объявлен здесь (в Marketplace), реализуется в
 * Payment\Infrastructure\Adapters\YookassaPaymentGateway и байндится в PaymentServiceProvider.
 * Marketplace не знает про ЮKassa и HTTP — только про эти примитивы. См. docs/rules/01-backend.md
 * ("явный Application-контракт").
 */
interface PaymentGatewayInterface
{
    public function initiate(Id $orderId, Money $amount, string $returnUrl): PaymentInitiationResult;
}
