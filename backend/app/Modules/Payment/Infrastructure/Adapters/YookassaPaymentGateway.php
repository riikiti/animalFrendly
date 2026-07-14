<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Adapters;

use App\Modules\Marketplace\Application\Contracts\PaymentGatewayInterface;
use App\Modules\Marketplace\Application\Contracts\PaymentInitiationResult;
use App\Modules\Payment\Application\Services\InitiatePaymentService;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

/**
 * Единственное место, где модуль Payment "знает" про Marketplace — тонкий адаптер контракта,
 * объявленного в Marketplace\Application\Contracts\PaymentGatewayInterface. Байндится в
 * PaymentServiceProvider. См. docs/rules/01-backend.md.
 */
final class YookassaPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly InitiatePaymentService $initiatePayment) {}

    public function initiate(Id $orderId, Money $amount, string $returnUrl): PaymentInitiationResult
    {
        $result = $this->initiatePayment->initiate('order', $orderId, $amount, $returnUrl);

        return new PaymentInitiationResult($result->confirmationUrl, $result->yookassaPaymentId);
    }
}
