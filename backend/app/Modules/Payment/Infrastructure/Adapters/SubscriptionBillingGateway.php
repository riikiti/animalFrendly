<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Adapters;

use App\Modules\Payment\Application\Services\InitiatePaymentService;
use App\Modules\Payment\Infrastructure\Jobs\ChargeRecurringSubscriptionJob;
use App\Modules\Subscription\Application\Contracts\PaymentInitiationResult;
use App\Modules\Subscription\Application\Contracts\SubscriptionBillingGatewayInterface;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

/**
 * Единственное место, где модуль Payment "знает" про Subscription — тонкий адаптер контракта,
 * объявленного в Subscription\Application\Contracts\SubscriptionBillingGatewayInterface.
 * Байндится в PaymentServiceProvider. См. docs/rules/01-backend.md.
 */
final class SubscriptionBillingGateway implements SubscriptionBillingGatewayInterface
{
    public function __construct(private readonly InitiatePaymentService $initiatePayment) {}

    public function initiateFirstPayment(Id $subscriptionId, Money $amount, string $returnUrl): PaymentInitiationResult
    {
        $result = $this->initiatePayment->initiate('subscription', $subscriptionId, $amount, $returnUrl, savePaymentMethod: true);

        return new PaymentInitiationResult($result->confirmationUrl, $result->yookassaPaymentId);
    }

    public function chargeRecurring(Id $subscriptionId, string $paymentMethodId, Money $amount, string $periodLabel): void
    {
        ChargeRecurringSubscriptionJob::dispatch(
            $subscriptionId->toString(),
            $paymentMethodId,
            $amount->minorUnits,
            $amount->currency,
            $periodLabel,
        );
    }
}
