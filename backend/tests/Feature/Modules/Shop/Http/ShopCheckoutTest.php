<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Payment\Domain\Events\PaymentSucceeded;
use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Models\ShopCategory;
use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Models\ShopProduct;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

function checkoutCategory(): ShopCategory
{
    // firstOrCreate: в тестах на нескольких продавцов помощник вызывается по разу на товар.
    return ShopCategory::query()->firstOrCreate(
        ['slug' => 'food'],
        ['name' => 'Корма', 'position' => 10],
    );
}

/**
 * @param  array<string, mixed>  $attributes
 */
function checkoutProduct(User $seller, array $attributes = []): ShopProduct
{
    return ShopProduct::query()->create(array_merge([
        'seller_id' => $seller->id,
        'category_id' => checkoutCategory()->id,
        'title' => 'Корм для щенков',
        'price_amount' => 100000,
        'currency' => 'RUB',
        'stock' => 5,
        'status' => 'published',
    ], $attributes));
}

it('turns the cart into an order and adds the delivery price', function (): void {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $product = checkoutProduct($seller);

    $this->actingAs($buyer)
        ->postJson('/api/v1/shop/cart', ['product_id' => $product->id, 'quantity' => 2])
        ->assertOk();

    $response = $this->actingAs($buyer)->postJson('/api/v1/shop/orders', [
        'delivery_method' => 'pvz',
        'delivery_address' => 'Москва, Тверская 1',
    ])->assertCreated();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.items_amount'))->toBe(200000)
        ->and($response->json('data.0.delivery_amount'))->toBe(20000)
        ->and($response->json('data.0.amount'))->toBe(220000)
        ->and($response->json('amount'))->toBe(220000)
        ->and($response->json('data.0.status'))->toBe('pending_payment');

    // Корзина после оформления пуста, остаток списан.
    $cart = $this->actingAs($buyer)->getJson('/api/v1/shop/cart')->assertOk();
    expect($cart->json('data.items'))->toBeEmpty()
        ->and(ShopProduct::query()->find($product->id)->stock)->toBe(3);
});

it('splits a multi-seller cart into an order per seller under one payment', function (): void {
    $buyer = User::factory()->create();
    $firstSeller = User::factory()->create();
    $secondSeller = User::factory()->create();

    $first = checkoutProduct($firstSeller, ['title' => 'Корм', 'price_amount' => 100000]);
    $second = checkoutProduct($secondSeller, ['title' => 'Лежанка', 'price_amount' => 250000]);

    foreach ([$first, $second] as $product) {
        $this->actingAs($buyer)
            ->postJson('/api/v1/shop/cart', ['product_id' => $product->id])
            ->assertOk();
    }

    $created = $this->actingAs($buyer)->postJson('/api/v1/shop/orders', [
        'delivery_method' => 'pvz',
        'delivery_address' => 'Москва',
    ])->assertCreated();

    // Два заказа, у каждого своя доставка: посылки едут от разных продавцов.
    expect($created->json('data'))->toHaveCount(2)
        ->and($created->json('amount'))->toBe(100000 + 250000 + 2 * 20000);

    $sellers = array_column($created->json('data'), 'seller_id');
    expect($sellers)->toContain($firstSeller->id)->toContain($secondSeller->id);

    // Один платёж на оформление переводит на эскроу оба заказа.
    app(DomainEventDispatcherInterface::class)->dispatch(new PaymentSucceeded(
        'shop_checkout',
        Id::fromString($created->json('checkout_id')),
        Money::fromMinorUnits($created->json('amount')),
        new DateTimeImmutable,
        [],
    ));

    $orders = $this->actingAs($buyer)->getJson('/api/v1/shop/orders')->assertOk();

    expect(array_column($orders->json('data'), 'status'))->toBe(['paid_escrow', 'paid_escrow']);
});

it('requires an address for delivery but not for pickup', function (): void {
    $buyer = User::factory()->create();
    $product = checkoutProduct(User::factory()->create());

    $this->actingAs($buyer)->postJson('/api/v1/shop/cart', ['product_id' => $product->id])->assertOk();

    $this->actingAs($buyer)
        ->postJson('/api/v1/shop/orders', ['delivery_method' => 'courier'])
        ->assertStatus(422);

    $this->actingAs($buyer)
        ->postJson('/api/v1/shop/orders', ['delivery_method' => 'pickup'])
        ->assertCreated();
});

it('refuses to check out an empty cart', function (): void {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/shop/orders', ['delivery_method' => 'pickup'])
        ->assertStatus(422);
});

it('holds the money in escrow and takes commission only from the goods', function (): void {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $product = checkoutProduct($seller);

    $this->actingAs($buyer)->postJson('/api/v1/shop/cart', ['product_id' => $product->id])->assertOk();
    $created = $this->actingAs($buyer)->postJson('/api/v1/shop/orders', [
        'delivery_method' => 'pvz',
        'delivery_address' => 'Москва',
    ])->assertCreated();

    $orderId = $created->json('data.0.id');

    app(DomainEventDispatcherInterface::class)->dispatch(new PaymentSucceeded(
        'shop_checkout',
        Id::fromString($created->json('checkout_id')),
        Money::fromMinorUnits(120000),
        new DateTimeImmutable,
        [],
    ));

    $order = $this->actingAs($buyer)->getJson("/api/v1/shop/orders/{$orderId}")->assertOk();

    // 5% с товара на 1000 ₽ — 50 ₽; доставка уходит продавцу целиком.
    expect($order->json('data.status'))->toBe('paid_escrow')
        ->and($order->json('data.commission_amount'))->toBe(5000)
        ->and($order->json('data.payout_amount'))->toBe(115000)
        ->and($order->json('data.escrow_hold_until'))->not->toBeNull();
});

it('completes the order only after both sides confirm', function (): void {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $product = checkoutProduct($seller);

    $this->actingAs($buyer)->postJson('/api/v1/shop/cart', ['product_id' => $product->id])->assertOk();
    $created = $this->actingAs($buyer)->postJson('/api/v1/shop/orders', ['delivery_method' => 'pickup']);
    $orderId = $created->json('data.0.id');

    app(DomainEventDispatcherInterface::class)->dispatch(new PaymentSucceeded(
        'shop_checkout',
        Id::fromString($created->json('checkout_id')),
        Money::fromMinorUnits(100000),
        new DateTimeImmutable,
        [],
    ));

    $afterBuyer = $this->actingAs($buyer)->postJson("/api/v1/shop/orders/{$orderId}/confirm")->assertOk();
    expect($afterBuyer->json('data.status'))->toBe('paid_escrow');

    $afterSeller = $this->actingAs($seller)->postJson("/api/v1/shop/orders/{$orderId}/confirm")->assertOk();
    expect($afterSeller->json('data.status'))->toBe('completed');
});

it('returns goods to stock when an unpaid order is cancelled', function (): void {
    $buyer = User::factory()->create();
    $product = checkoutProduct(User::factory()->create(), ['stock' => 4]);

    $this->actingAs($buyer)
        ->postJson('/api/v1/shop/cart', ['product_id' => $product->id, 'quantity' => 3])
        ->assertOk();

    $orderId = $this->actingAs($buyer)
        ->postJson('/api/v1/shop/orders', ['delivery_method' => 'pickup'])
        ->json('data.0.id');

    expect(ShopProduct::query()->find($product->id)->stock)->toBe(1);

    $this->actingAs($buyer)->postJson("/api/v1/shop/orders/{$orderId}/cancel")->assertOk();

    expect(ShopProduct::query()->find($product->id)->stock)->toBe(4);
});

it('hides someone else order', function (): void {
    $buyer = User::factory()->create();
    $product = checkoutProduct(User::factory()->create());

    $this->actingAs($buyer)->postJson('/api/v1/shop/cart', ['product_id' => $product->id])->assertOk();
    $orderId = $this->actingAs($buyer)
        ->postJson('/api/v1/shop/orders', ['delivery_method' => 'pickup'])
        ->json('data.0.id');

    $this->actingAs(User::factory()->create())
        ->getJson("/api/v1/shop/orders/{$orderId}")
        ->assertForbidden();
});

it('lets only the seller mark the order as shipped', function (): void {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $product = checkoutProduct($seller);

    $this->actingAs($buyer)->postJson('/api/v1/shop/cart', ['product_id' => $product->id])->assertOk();
    $created = $this->actingAs($buyer)->postJson('/api/v1/shop/orders', ['delivery_method' => 'pickup']);
    $orderId = $created->json('data.0.id');

    app(DomainEventDispatcherInterface::class)->dispatch(new PaymentSucceeded(
        'shop_checkout',
        Id::fromString($created->json('checkout_id')),
        Money::fromMinorUnits(100000),
        new DateTimeImmutable,
        [],
    ));

    $this->actingAs($buyer)->postJson("/api/v1/shop/orders/{$orderId}/ship")->assertForbidden();

    $shipped = $this->actingAs($seller)->postJson("/api/v1/shop/orders/{$orderId}/ship")->assertOk();
    expect($shipped->json('data.status'))->toBe('shipped');
});

it('ignores a payment event for another payable type', function (): void {
    $buyer = User::factory()->create();
    $product = checkoutProduct(User::factory()->create());

    $this->actingAs($buyer)->postJson('/api/v1/shop/cart', ['product_id' => $product->id])->assertOk();
    $created = $this->actingAs($buyer)->postJson('/api/v1/shop/orders', ['delivery_method' => 'pickup']);
    $orderId = $created->json('data.0.id');

    app(DomainEventDispatcherInterface::class)->dispatch(new PaymentSucceeded(
        'order',
        Id::fromString($orderId),
        Money::fromMinorUnits(100000),
        new DateTimeImmutable,
        [],
    ));

    $order = $this->actingAs($buyer)->getJson("/api/v1/shop/orders/{$orderId}")->assertOk();

    expect($order->json('data.status'))->toBe('pending_payment');
});
