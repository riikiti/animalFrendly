<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\External;

use App\Modules\Payment\Application\Contracts\YookassaClientInterface;
use App\Shared\Domain\ValueObjects\Money;

/**
 * Фоллбэк для локальной разработки и E2E, когда YOOKASSA_SHOP_ID/SECRET_KEY не заполнены —
 * тот же принцип, что MAIL_MAILER=log. Байндится вместо YookassaClient в
 * PaymentServiceProvider::register(). Не делает реальных HTTP-вызовов; confirmation_url —
 * это сам return_url (браузер сразу попадает на страницу заказа, минуя хостинговую страницу
 * оплаты ЮKassa), id детерминирован от idempotency-ключа, чтобы E2E-тесты могли сами
 * сформировать вебхук-payload, не имея доступа к БД приложения.
 */
final class NullYookassaClient implements YookassaClientInterface
{
    public function createPayment(
        Money $amount,
        string $description,
        string $returnUrl,
        string $idempotencyKey,
        bool $savePaymentMethod = false,
    ): array {
        return [
            'id' => "local-{$idempotencyKey}",
            'confirmation' => ['confirmation_url' => $returnUrl],
            ...($savePaymentMethod ? ['payment_method' => ['id' => "local-pm-{$idempotencyKey}", 'saved' => true]] : []),
        ];
    }

    public function chargeWithSavedMethod(
        Money $amount,
        string $paymentMethodId,
        string $description,
        string $idempotencyKey,
    ): array {
        return ['id' => "local-{$idempotencyKey}", 'status' => 'pending'];
    }

    public function createRefund(string $yookassaPaymentId, Money $amount, string $idempotencyKey): array
    {
        return ['id' => "local-{$idempotencyKey}"];
    }

    public function createPayout(Money $amount, string $idempotencyKey): array
    {
        return ['id' => "local-{$idempotencyKey}"];
    }
}
