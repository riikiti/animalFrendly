<?php

declare(strict_types=1);

use App\Modules\Payment\Domain\Exceptions\YookassaRequestFailedException;
use App\Modules\Payment\Infrastructure\External\YookassaClient;
use App\Shared\Domain\ValueObjects\Money;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'yookassa.base_url' => 'https://api.yookassa.ru/v3',
        'yookassa.shop_id' => 'shop-123',
        'yookassa.secret_key' => 'secret-456',
    ]);
});

it('sends a correctly shaped createPayment request with idempotency key and basic auth', function (): void {
    Http::fake([
        'https://api.yookassa.ru/v3/payments' => Http::response([
            'id' => 'yk-payment-1',
            'confirmation' => ['confirmation_url' => 'https://yookassa.ru/pay/1'],
        ], 200),
    ]);

    $client = new YookassaClient;
    $result = $client->createPayment(Money::fromMinorUnits(150_00), 'Оплата заказа', 'https://app.test/orders/1', 'order-1:create');

    expect($result['id'])->toBe('yk-payment-1')
        ->and($result['confirmation']['confirmation_url'])->toBe('https://yookassa.ru/pay/1');

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.yookassa.ru/v3/payments'
            && $request->hasHeader('Idempotence-Key', 'order-1:create')
            && $request['amount'] === ['value' => '150.00', 'currency' => 'RUB']
            && $request['confirmation']['return_url'] === 'https://app.test/orders/1'
            && str_starts_with((string) $request->header('Authorization')[0], 'Basic ');
    });
});

it('sends save_payment_method when requested on createPayment', function (): void {
    Http::fake([
        'https://api.yookassa.ru/v3/payments' => Http::response([
            'id' => 'yk-payment-2',
            'confirmation' => ['confirmation_url' => 'https://yookassa.ru/pay/2'],
        ], 200),
    ]);

    $client = new YookassaClient;
    $client->createPayment(
        Money::fromMinorUnits(29_900),
        'Оформление подписки',
        'https://app.test/subscription/status',
        'sub-1:create',
        savePaymentMethod: true,
    );

    Http::assertSent(fn (Request $request): bool => $request['save_payment_method'] === true);
});

it('does not send save_payment_method by default', function (): void {
    Http::fake([
        'https://api.yookassa.ru/v3/payments' => Http::response([
            'id' => 'yk-payment-3',
            'confirmation' => ['confirmation_url' => 'https://yookassa.ru/pay/3'],
        ], 200),
    ]);

    $client = new YookassaClient;
    $client->createPayment(Money::fromMinorUnits(100_00), 'Оплата заказа', 'https://app.test/orders/1', 'order-2:create');

    Http::assertSent(fn (Request $request): bool => ! array_key_exists('save_payment_method', $request->data()));
});

it('charges a saved payment method without a confirmation redirect', function (): void {
    Http::fake([
        'https://api.yookassa.ru/v3/payments' => Http::response(['id' => 'yk-payment-4', 'status' => 'pending'], 200),
    ]);

    $client = new YookassaClient;
    $result = $client->chargeWithSavedMethod(
        Money::fromMinorUnits(29_900),
        'pm-123',
        'Автопродление подписки',
        'sub-1:renew:2026-08',
    );

    expect($result['id'])->toBe('yk-payment-4');

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.yookassa.ru/v3/payments'
            && $request['payment_method_id'] === 'pm-123'
            && $request->hasHeader('Idempotence-Key', 'sub-1:renew:2026-08')
            && ! array_key_exists('confirmation', $request->data());
    });
});

it('sends a correctly shaped createRefund request', function (): void {
    Http::fake(['https://api.yookassa.ru/v3/refunds' => Http::response(['id' => 'yk-refund-1'], 200)]);

    $client = new YookassaClient;
    $client->createRefund('yk-payment-1', Money::fromMinorUnits(150_00), 'order-1:refund');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.yookassa.ru/v3/refunds'
        && $request['payment_id'] === 'yk-payment-1'
        && $request->hasHeader('Idempotence-Key', 'order-1:refund'));
});

it('throws YookassaRequestFailedException on a non-2xx response', function (): void {
    Http::fake(['https://api.yookassa.ru/v3/payments' => Http::response(['type' => 'error'], 400)]);

    $client = new YookassaClient;
    $client->createPayment(Money::fromMinorUnits(100_00), 'x', 'https://app.test', 'k');
})->throws(YookassaRequestFailedException::class);
