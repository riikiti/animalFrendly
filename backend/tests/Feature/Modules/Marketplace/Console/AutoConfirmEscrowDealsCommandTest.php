<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Species;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Marketplace\Infrastructure\Persistence\Eloquent\Models\Order as EloquentOrder;
use App\Modules\Payment\Application\Contracts\YookassaClientInterface;
use App\Modules\Payment\Infrastructure\Persistence\Eloquent\Models\Payment as EloquentPayment;
use App\Modules\Payment\Infrastructure\Persistence\Eloquent\Models\Payout as EloquentPayout;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeYookassaClient;

it('auto-confirms paid_escrow orders whose hold period has expired', function (): void {
    $this->app->bind(YookassaClientInterface::class, FakeYookassaClient::class);

    $dog = Species::query()->firstOrCreate(['slug' => 'dog'], ['name_ru' => 'Собака', 'is_active' => true]);
    $seller = User::factory()->create();
    $buyer = User::factory()->create();

    Sanctum::actingAs($seller);
    $listingId = $this->postJson('/api/v1/listings', [
        'species_id' => $dog->id, 'name' => 'Барон', 'sex' => 'male', 'price_amount' => 100_000,
    ])->json('data.id');
    $this->postJson("/api/v1/listings/{$listingId}/publish")->assertOk();

    Sanctum::actingAs($buyer);
    $orderId = $this->postJson("/api/v1/listings/{$listingId}/orders")->json('data.order.id');

    $yookassaPaymentId = EloquentPayment::query()->where('payable_id', $orderId)->value('yookassa_payment_id');
    $this->postJson('/api/v1/payments/webhooks/yookassa', [
        'event' => 'payment.succeeded',
        'object' => ['id' => $yookassaPaymentId, 'status' => 'succeeded'],
    ])->assertOk();

    // Симулируем истечение 7-дневного удержания — напрямую в БД, т.к. домен не даёт "состарить"
    // сделку иначе.
    EloquentOrder::query()->where('id', $orderId)->update(['escrow_hold_until' => now()->subDay()]);

    Artisan::call('deals:auto-confirm');

    $order = $this->getJson("/api/v1/orders/{$orderId}")->json('data');
    expect($order['status'])->toBe('completed');

    $payout = EloquentPayout::query()->where('order_id', $orderId)->first();
    expect($payout)->not->toBeNull();
});

it('leaves orders with a hold period still in the future untouched', function (): void {
    $this->app->bind(YookassaClientInterface::class, FakeYookassaClient::class);

    $dog = Species::query()->firstOrCreate(['slug' => 'dog'], ['name_ru' => 'Собака', 'is_active' => true]);
    $seller = User::factory()->create();
    $buyer = User::factory()->create();

    Sanctum::actingAs($seller);
    $listingId = $this->postJson('/api/v1/listings', [
        'species_id' => $dog->id, 'name' => 'Барон', 'sex' => 'male', 'price_amount' => 100_000,
    ])->json('data.id');
    $this->postJson("/api/v1/listings/{$listingId}/publish")->assertOk();

    Sanctum::actingAs($buyer);
    $orderId = $this->postJson("/api/v1/listings/{$listingId}/orders")->json('data.order.id');

    $yookassaPaymentId = EloquentPayment::query()->where('payable_id', $orderId)->value('yookassa_payment_id');
    $this->postJson('/api/v1/payments/webhooks/yookassa', [
        'event' => 'payment.succeeded',
        'object' => ['id' => $yookassaPaymentId, 'status' => 'succeeded'],
    ])->assertOk();

    Artisan::call('deals:auto-confirm');

    $order = $this->getJson("/api/v1/orders/{$orderId}")->json('data');
    expect($order['status'])->toBe('paid_escrow');
});
