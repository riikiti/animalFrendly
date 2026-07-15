<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Payment\Application\Contracts\YookassaClientInterface;
use App\Modules\Payment\Infrastructure\Persistence\Eloquent\Models\Payment as EloquentPayment;
use App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Models\SubscriptionPlan as EloquentSubscriptionPlan;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeYookassaClient;

function seedTestPlan(string $slug = 'plus', array $features = []): EloquentSubscriptionPlan
{
    return EloquentSubscriptionPlan::query()->create([
        'slug' => $slug,
        'name_ru' => ucfirst($slug),
        'price_amount' => 29_900,
        'currency' => 'RUB',
        'period' => 'month',
        'features' => array_merge(['marketplace_commission_bps' => 500], $features),
        'is_active' => true,
    ]);
}

it('subscribes to a plan and activates it once payment.succeeded arrives with a saved method', function (): void {
    app()->bind(YookassaClientInterface::class, FakeYookassaClient::class);
    seedTestPlan('plus');

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/subscriptions', ['plan_slug' => 'plus']);
    $response->assertCreated()->assertJsonPath('data.subscription.status', 'pending_payment');

    $subscriptionId = $response->json('data.subscription.id');
    $yookassaPaymentId = EloquentPayment::query()->where('payable_id', $subscriptionId)->value('yookassa_payment_id');

    $this->postJson('/api/v1/payments/webhooks/yookassa', [
        'event' => 'payment.succeeded',
        'object' => [
            'id' => $yookassaPaymentId,
            'status' => 'succeeded',
            'payment_method' => ['id' => 'pm-test-1', 'saved' => true],
        ],
    ])->assertOk();

    $me = $this->getJson('/api/v1/subscriptions/me')->json('data');
    expect($me['subscription']['status'])->toBe('active')
        ->and($me['subscription']['current_period_ends_at'])->not->toBeNull()
        ->and($me['plan']['slug'])->toBe('plus');
});

it('is idempotent when the same payment.succeeded webhook arrives twice', function (): void {
    app()->bind(YookassaClientInterface::class, FakeYookassaClient::class);
    seedTestPlan('plus');

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $subscriptionId = $this->postJson('/api/v1/subscriptions', ['plan_slug' => 'plus'])->json('data.subscription.id');
    $yookassaPaymentId = EloquentPayment::query()->where('payable_id', $subscriptionId)->value('yookassa_payment_id');

    $payload = [
        'event' => 'payment.succeeded',
        'object' => ['id' => $yookassaPaymentId, 'status' => 'succeeded', 'payment_method' => ['id' => 'pm-test-2', 'saved' => true]],
    ];

    $this->postJson('/api/v1/payments/webhooks/yookassa', $payload)->assertOk();
    $firstPeriodEnd = $this->getJson('/api/v1/subscriptions/me')->json('data.subscription.current_period_ends_at');

    $this->postJson('/api/v1/payments/webhooks/yookassa', $payload)->assertOk();
    $secondPeriodEnd = $this->getJson('/api/v1/subscriptions/me')->json('data.subscription.current_period_ends_at');

    expect($secondPeriodEnd)->toBe($firstPeriodEnd);
});

it('rejects subscribing twice while a subscription is already pending or active', function (): void {
    app()->bind(YookassaClientInterface::class, FakeYookassaClient::class);
    seedTestPlan('plus');

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/subscriptions', ['plan_slug' => 'plus'])->assertCreated();
    $this->postJson('/api/v1/subscriptions', ['plan_slug' => 'plus'])->assertUnprocessable();
});

it('cancels auto-renew on an active subscription', function (): void {
    app()->bind(YookassaClientInterface::class, FakeYookassaClient::class);
    seedTestPlan('plus');

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $subscriptionId = $this->postJson('/api/v1/subscriptions', ['plan_slug' => 'plus'])->json('data.subscription.id');
    $yookassaPaymentId = EloquentPayment::query()->where('payable_id', $subscriptionId)->value('yookassa_payment_id');

    $this->postJson('/api/v1/payments/webhooks/yookassa', [
        'event' => 'payment.succeeded',
        'object' => ['id' => $yookassaPaymentId, 'status' => 'succeeded', 'payment_method' => ['id' => 'pm-test-3', 'saved' => true]],
    ])->assertOk();

    $this->postJson('/api/v1/subscriptions/cancel')
        ->assertOk()
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.auto_renew', false);
});

it('cancels the pending subscription when the first payment fails', function (): void {
    app()->bind(YookassaClientInterface::class, FakeYookassaClient::class);
    seedTestPlan('plus');

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $subscriptionId = $this->postJson('/api/v1/subscriptions', ['plan_slug' => 'plus'])->json('data.subscription.id');
    $yookassaPaymentId = EloquentPayment::query()->where('payable_id', $subscriptionId)->value('yookassa_payment_id');

    $this->postJson('/api/v1/payments/webhooks/yookassa', [
        'event' => 'payment.canceled',
        'object' => ['id' => $yookassaPaymentId, 'status' => 'canceled'],
    ])->assertOk();

    // Истёкшая (так и не активированная) подписка больше не "текущая" — /me возвращает null,
    // фронтенд трактует это как бесплатный тариф.
    $me = $this->getJson('/api/v1/subscriptions/me')->json('data');
    expect($me['subscription'])->toBeNull();

    $resubscribeResponse = $this->postJson('/api/v1/subscriptions', ['plan_slug' => 'plus']);
    $resubscribeResponse->assertCreated();
});
