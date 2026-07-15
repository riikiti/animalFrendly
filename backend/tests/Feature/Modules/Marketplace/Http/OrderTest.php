<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Species;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Payment\Application\Contracts\YookassaClientInterface;
use App\Modules\Payment\Infrastructure\Persistence\Eloquent\Models\Payment as EloquentPayment;
use App\Modules\Payment\Infrastructure\Persistence\Eloquent\Models\Payout as EloquentPayout;
use App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Models\Subscription as EloquentSubscription;
use App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Models\SubscriptionPlan as EloquentSubscriptionPlan;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeYookassaClient;

function purchaseTestListing(int $priceAmount = 100_000, ?User $seller = null): array
{
    app()->bind(YookassaClientInterface::class, FakeYookassaClient::class);

    $dog = Species::query()->firstOrCreate(['slug' => 'dog'], ['name_ru' => 'Собака', 'is_active' => true]);
    $seller ??= User::factory()->create();
    $buyer = User::factory()->create();

    Sanctum::actingAs($seller);
    $listingId = test()->postJson('/api/v1/listings', [
        'species_id' => $dog->id,
        'name' => 'Барон',
        'sex' => 'male',
        'price_amount' => $priceAmount,
    ])->json('data.id');
    test()->postJson("/api/v1/listings/{$listingId}/publish")->assertOk();

    Sanctum::actingAs($buyer);
    $purchaseResponse = test()->postJson("/api/v1/listings/{$listingId}/orders");
    $purchaseResponse->assertCreated()->assertJsonPath('data.order.status', 'pending_payment');

    $orderId = $purchaseResponse->json('data.order.id');
    $yookassaPaymentId = EloquentPayment::query()->where('payable_id', $orderId)->value('yookassa_payment_id');

    return compact('seller', 'buyer', 'listingId', 'orderId', 'yookassaPaymentId');
}

function sendSucceededWebhook(string $yookassaPaymentId): void
{
    test()->postJson('/api/v1/payments/webhooks/yookassa', [
        'event' => 'payment.succeeded',
        'object' => ['id' => $yookassaPaymentId, 'status' => 'succeeded'],
    ])->assertOk();
}

it('reserves the listing on purchase and marks it sold once payment succeeds', function (): void {
    ['seller' => $seller, 'listingId' => $listingId, 'orderId' => $orderId, 'yookassaPaymentId' => $yookassaPaymentId] = purchaseTestListing();

    Sanctum::actingAs($seller);
    $listingBeforePayment = collect($this->getJson('/api/v1/listings/me')->json('data'))->firstWhere('id', $listingId);
    expect($listingBeforePayment['status'])->toBe('reserved');

    sendSucceededWebhook($yookassaPaymentId);

    $order = $this->getJson("/api/v1/orders/{$orderId}")->json('data');
    expect($order['status'])->toBe('paid_escrow')
        ->and($order['commission_amount'])->toBe(5_000)
        ->and($order['payout_amount'])->toBe(95_000);

    $listingAfterPayment = collect($this->getJson('/api/v1/listings/me')->json('data'))->firstWhere('id', $listingId);
    expect($listingAfterPayment['status'])->toBe('sold');
});

it('completes the order once both sides confirm and queues a payout', function (): void {
    ['seller' => $seller, 'buyer' => $buyer, 'orderId' => $orderId, 'yookassaPaymentId' => $yookassaPaymentId] = purchaseTestListing();
    sendSucceededWebhook($yookassaPaymentId);

    Sanctum::actingAs($buyer);
    $this->postJson("/api/v1/orders/{$orderId}/confirm")->assertOk()->assertJsonPath('data.status', 'paid_escrow');

    Sanctum::actingAs($seller);
    $this->postJson("/api/v1/orders/{$orderId}/confirm")->assertOk()->assertJsonPath('data.status', 'completed');

    $payout = EloquentPayout::query()->where('order_id', $orderId)->first();
    expect($payout)->not->toBeNull()
        ->and($payout->status)->toBe('pending')
        ->and((int) $payout->amount)->toBe(95_000);
});

it('lets the buyer cancel before paying and frees the listing', function (): void {
    ['buyer' => $buyer, 'listingId' => $listingId, 'orderId' => $orderId, 'seller' => $seller] = purchaseTestListing();

    Sanctum::actingAs($buyer);
    $this->postJson("/api/v1/orders/{$orderId}/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');

    Sanctum::actingAs($seller);
    $listing = collect($this->getJson('/api/v1/listings/me')->json('data'))->firstWhere('id', $listingId);
    expect($listing['status'])->toBe('published');
});

it('cancels the order and frees the listing when payment.canceled arrives', function (): void {
    ['listingId' => $listingId, 'orderId' => $orderId, 'seller' => $seller, 'yookassaPaymentId' => $yookassaPaymentId] = purchaseTestListing();

    $this->postJson('/api/v1/payments/webhooks/yookassa', [
        'event' => 'payment.canceled',
        'object' => ['id' => $yookassaPaymentId, 'status' => 'canceled'],
    ])->assertOk();

    $order = $this->getJson("/api/v1/orders/{$orderId}")->json('data');
    expect($order['status'])->toBe('cancelled');

    Sanctum::actingAs($seller);
    $listing = collect($this->getJson('/api/v1/listings/me')->json('data'))->firstWhere('id', $listingId);
    expect($listing['status'])->toBe('published');
});

it('lets a party open a dispute and an admin resolve it seller_wins -> completed', function (): void {
    ['seller' => $seller, 'buyer' => $buyer, 'orderId' => $orderId, 'yookassaPaymentId' => $yookassaPaymentId] = purchaseTestListing();
    sendSucceededWebhook($yookassaPaymentId);

    Sanctum::actingAs($buyer);
    $disputeId = $this->postJson("/api/v1/orders/{$orderId}/disputes", ['reason' => 'Питомец не соответствует описанию'])
        ->assertCreated()
        ->json('data.id');

    Sanctum::actingAs(User::factory()->create());
    $this->postJson("/api/v1/disputes/{$disputeId}/resolve", ['resolution' => 'seller_wins'])->assertForbidden();

    Sanctum::actingAs(User::factory()->create(['account_type' => 'moderator']));
    $this->postJson("/api/v1/disputes/{$disputeId}/resolve", ['resolution' => 'seller_wins'])
        ->assertOk()
        ->assertJsonPath('data.resolution', 'seller_wins');

    Sanctum::actingAs($buyer);
    $order = $this->getJson("/api/v1/orders/{$orderId}")->json('data');
    expect($order['status'])->toBe('completed');
});

it('charges a lower commission for a seller with an active premium subscription', function (): void {
    $plan = EloquentSubscriptionPlan::query()->create([
        'slug' => 'premium',
        'name_ru' => 'Premium',
        'price_amount' => 59_900,
        'currency' => 'RUB',
        'period' => 'month',
        'features' => ['marketplace_commission_bps' => 400],
        'is_active' => true,
    ]);

    $seller = User::factory()->create();

    EloquentSubscription::query()->create([
        'id' => (string) Str::ulid(),
        'user_id' => $seller->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'started_at' => now(),
        'current_period_ends_at' => now()->addMonth(),
        'auto_renew' => true,
    ]);

    ['orderId' => $orderId, 'yookassaPaymentId' => $yookassaPaymentId] = purchaseTestListing(100_000, $seller);
    sendSucceededWebhook($yookassaPaymentId);

    Sanctum::actingAs($seller);
    $order = $this->getJson("/api/v1/orders/{$orderId}")->json('data');

    expect($order['status'])->toBe('paid_escrow')
        ->and($order['commission_amount'])->toBe(4_000)
        ->and($order['payout_amount'])->toBe(96_000);
});

it('resolves a dispute buyer_wins and refunds the order', function (): void {
    ['buyer' => $buyer, 'orderId' => $orderId, 'yookassaPaymentId' => $yookassaPaymentId] = purchaseTestListing();
    sendSucceededWebhook($yookassaPaymentId);

    Sanctum::actingAs($buyer);
    $disputeId = $this->postJson("/api/v1/orders/{$orderId}/disputes", ['reason' => 'Не приехал'])->json('data.id');

    Sanctum::actingAs(User::factory()->create(['account_type' => 'admin']));
    $this->postJson("/api/v1/disputes/{$disputeId}/resolve", ['resolution' => 'buyer_wins'])
        ->assertOk()
        ->assertJsonPath('data.resolution', 'buyer_wins');

    Sanctum::actingAs($buyer);
    $order = $this->getJson("/api/v1/orders/{$orderId}")->json('data');
    expect($order['status'])->toBe('refunded');
});
