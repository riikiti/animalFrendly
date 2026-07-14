<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\External;

use App\Modules\Payment\Application\Contracts\YookassaClientInterface;
use App\Modules\Payment\Domain\Exceptions\YookassaRequestFailedException;
use App\Shared\Domain\ValueObjects\Money;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * HTTP-клиент ЮKassa API v3 (https://yookassa.ru/developers/api). Реальных ключей ещё нет —
 * рабочий адаптер, конфигурируется через config/yookassa.php, в тестах подменяется через
 * Http::fake() (см. tests/Integration/Modules/Payment/YookassaClientTest.php).
 */
final class YookassaClient implements YookassaClientInterface
{
    public function createPayment(Money $amount, string $description, string $returnUrl, string $idempotencyKey): array
    {
        $response = $this->request($idempotencyKey)->post('/payments', [
            'amount' => $this->formatAmount($amount),
            'capture' => true,
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $returnUrl,
            ],
            'description' => $description,
        ]);

        return $this->decode($response, 'createPayment');
    }

    public function createRefund(string $yookassaPaymentId, Money $amount, string $idempotencyKey): array
    {
        $response = $this->request($idempotencyKey)->post('/refunds', [
            'payment_id' => $yookassaPaymentId,
            'amount' => $this->formatAmount($amount),
        ]);

        return $this->decode($response, 'createRefund');
    }

    public function createPayout(Money $amount, string $idempotencyKey): array
    {
        // Требует payout_destination_data (реквизиты продавца), которые пока не собираются
        // (см. Context в плане фазы Marketplace) — метод рабочий на случай, если
        // payouts_enabled включат до того, как это будет реализовано, вызов просто вернёт
        // ошибку ЮKassa, которую CreatePayoutService корректно превращает в Payout::failed.
        $response = $this->request($idempotencyKey)->post('/payouts', [
            'amount' => $this->formatAmount($amount),
        ]);

        return $this->decode($response, 'createPayout');
    }

    private function request(string $idempotencyKey): PendingRequest
    {
        return Http::baseUrl((string) config('yookassa.base_url'))
            ->withBasicAuth((string) config('yookassa.shop_id'), (string) config('yookassa.secret_key'))
            ->withHeaders(['Idempotence-Key' => $idempotencyKey])
            ->acceptJson()
            ->asJson();
    }

    /**
     * @return array{value: string, currency: string}
     */
    private function formatAmount(Money $amount): array
    {
        return [
            'value' => number_format($amount->minorUnits / 100, 2, '.', ''),
            'currency' => $amount->currency,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response, string $operation): array
    {
        if ($response->failed()) {
            throw YookassaRequestFailedException::create($operation, $response->body());
        }

        return $response->json() ?? [];
    }
}
