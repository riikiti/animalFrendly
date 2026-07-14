<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Services;

use App\Modules\Payment\Domain\Entities\Payment;
use App\Modules\Payment\Domain\Enums\PaymentStatus;
use App\Modules\Payment\Domain\Events\PaymentCanceled;
use App\Modules\Payment\Domain\Events\PaymentSucceeded;
use App\Modules\Payment\Domain\Repositories\PaymentRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Вызывается из ProcessYookassaWebhookJob. Идемпотентно: повторный вебхук с тем же событием
 * не меняет статус дважды и не диспатчит событие дважды, см. docs/rules/04-payments-escrow.md.
 */
final class ProcessWebhookService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly DomainEventDispatcherInterface $events,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function process(array $payload): void
    {
        $event = $payload['event'] ?? null;
        $yookassaPaymentId = $payload['object']['id'] ?? null;

        if (! is_string($event) || ! is_string($yookassaPaymentId)) {
            Log::warning('yookassa.webhook.malformed_payload', ['payload' => $payload]);

            return;
        }

        $payment = $this->payments->findByYookassaId($yookassaPaymentId);

        if ($payment === null) {
            Log::warning('yookassa.webhook.unknown_payment', ['yookassa_payment_id' => $yookassaPaymentId]);

            return;
        }

        /** @var array<string, mixed> $object */
        $object = $payload['object'];

        match ($event) {
            'payment.succeeded' => $this->handleSucceeded($payment, $object),
            'payment.canceled' => $this->handleCanceled($payment, $object),
            'refund.succeeded' => $this->handleRefunded($payment, $object),
            default => Log::info('yookassa.webhook.unhandled_event', ['event' => $event]),
        };
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function handleSucceeded(Payment $payment, array $object): void
    {
        if ($payment->status() === PaymentStatus::Succeeded) {
            return;
        }

        $payment->markSucceeded($object);
        $this->payments->save($payment);

        $this->events->dispatch(new PaymentSucceeded(
            $payment->payableType(),
            $payment->payableId(),
            $payment->amount(),
            new DateTimeImmutable,
        ));
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function handleCanceled(Payment $payment, array $object): void
    {
        if ($payment->status() === PaymentStatus::Canceled) {
            return;
        }

        $payment->markCanceled($object);
        $this->payments->save($payment);

        $this->events->dispatch(new PaymentCanceled($payment->payableType(), $payment->payableId(), new DateTimeImmutable));
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function handleRefunded(Payment $payment, array $object): void
    {
        if ($payment->status() === PaymentStatus::Refunded) {
            return;
        }

        $payment->markRefunded($object);
        $this->payments->save($payment);
    }
}
