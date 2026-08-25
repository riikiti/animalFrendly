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
 * Тип payable свой — shop_checkout: один платёж покрывает все заказы одного оформления,
 * и вебхук по нему разводит магазин, сделки маркетплейса и подписки.
 */
final class ShopPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly InitiatePaymentService $initiatePayment) {}

    public function initiate(Id $checkoutId, Money $amount, string $returnUrl): PaymentInitiationResult
    {
        $result = $this->initiatePayment->initiate('shop_checkout', $checkoutId, $amount, $returnUrl);

        return new PaymentInitiationResult($result->confirmationUrl, $result->yookassaPaymentId);
    }
}
