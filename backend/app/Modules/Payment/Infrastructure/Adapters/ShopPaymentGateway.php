<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Adapters;

use App\Modules\Payment\Application\Services\InitiatePaymentService;
use App\Modules\Shop\Application\Contracts\PaymentGatewayInterface;
use App\Modules\Shop\Application\Contracts\PaymentInitiationResult;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

/**
 * Тонкий адаптер контракта, объявленного в Shop\Application\Contracts\PaymentGatewayInterface.
 * Тип payable свой — shop_order, чтобы вебхук развёл заказы магазина и сделки маркетплейса.
 */
final class ShopPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly InitiatePaymentService $initiatePayment) {}

    public function initiate(Id $orderId, Money $amount, string $returnUrl): PaymentInitiationResult
    {
        $result = $this->initiatePayment->initiate('shop_order', $orderId, $amount, $returnUrl);

        return new PaymentInitiationResult($result->confirmationUrl, $result->yookassaPaymentId);
    }
}
