<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Models\ShopCategory;
use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Models\ShopProduct;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function shopCategory(): ShopCategory
{
    return ShopCategory::query()->create(['slug' => 'food', 'name' => 'Корма и лакомства', 'position' => 10]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function shopProduct(User $seller, ShopCategory $category, array $attributes = []): ShopProduct
{
    return ShopProduct::query()->create(array_merge([
        'seller_id' => $seller->id,
        'category_id' => $category->id,
        'title' => 'Корм для щенков',
        'price_amount' => 129000,
        'currency' => 'RUB',
        'stock' => 5,
        'status' => 'published',
    ], $attributes));
}

it('shows only published products that are in stock', function (): void {
    $seller = User::factory()->create();
    $category = shopCategory();

    shopProduct($seller, $category, ['title' => 'В продаже']);
    shopProduct($seller, $category, ['title' => 'Черновик', 'status' => 'draft']);
    shopProduct($seller, $category, ['title' => 'Кончился', 'stock' => 0]);

    $response = $this->getJson('/api/v1/shop/products')->assertOk();

    $titles = array_column($response->json('data'), 'title');

    expect($titles)->toBe(['В продаже']);
});

it('filters the storefront by category', function (): void {
    $seller = User::factory()->create();
    $food = shopCategory();
    $toys = ShopCategory::query()->create(['slug' => 'toys', 'name' => 'Игрушки', 'position' => 20]);

    shopProduct($seller, $food, ['title' => 'Корм']);
    shopProduct($seller, $toys, ['title' => 'Мячик']);

    $response = $this->getJson('/api/v1/shop/products?category=toys')->assertOk();

    expect(array_column($response->json('data'), 'title'))->toBe(['Мячик']);
});

it('adds a product to the cart and counts the total', function (): void {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $product = shopProduct($seller, shopCategory());

    $response = $this->actingAs($buyer)
        ->postJson('/api/v1/shop/cart', ['product_id' => $product->id, 'quantity' => 2])
        ->assertOk();

    expect($response->json('data.total_amount'))->toBe(258000)
        ->and($response->json('data.items'))->toHaveCount(1);
});

it('sums up repeated additions of the same product', function (): void {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $product = shopProduct($seller, shopCategory());

    $this->actingAs($buyer)->postJson('/api/v1/shop/cart', ['product_id' => $product->id])->assertOk();
    $response = $this->actingAs($buyer)
        ->postJson('/api/v1/shop/cart', ['product_id' => $product->id])
        ->assertOk();

    expect($response->json('data.items'))->toHaveCount(1)
        ->and($response->json('data.items.0.quantity'))->toBe(2);
});

it('refuses to put more than there is in stock', function (): void {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $product = shopProduct($seller, shopCategory(), ['stock' => 3]);

    $this->actingAs($buyer)
        ->postJson('/api/v1/shop/cart', ['product_id' => $product->id, 'quantity' => 4])
        ->assertStatus(422);
});

it('refuses to buy your own product', function (): void {
    $seller = User::factory()->create();
    $product = shopProduct($seller, shopCategory());

    $this->actingAs($seller)
        ->postJson('/api/v1/shop/cart', ['product_id' => $product->id])
        ->assertStatus(422);
});

it('keeps products of different sellers in one cart, grouped by seller', function (): void {
    $category = shopCategory();
    $buyer = User::factory()->create();
    $first = shopProduct(User::factory()->create(), $category);
    $second = shopProduct(User::factory()->create(), $category, ['title' => 'Чужой корм']);

    $this->actingAs($buyer)->postJson('/api/v1/shop/cart', ['product_id' => $first->id])->assertOk();

    $response = $this->actingAs($buyer)
        ->postJson('/api/v1/shop/cart', ['product_id' => $second->id])
        ->assertOk();

    // Оба товара лежат в корзине, но разложены по продавцам — на них уйдут разные заказы.
    expect($response->json('data.items'))->toHaveCount(2)
        ->and($response->json('data.groups'))->toHaveCount(2)
        ->and($response->json('data.total_amount'))->toBe(258000);
});

it('drops a cart line when the product is gone', function (): void {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $product = shopProduct($seller, shopCategory());

    $this->actingAs($buyer)->postJson('/api/v1/shop/cart', ['product_id' => $product->id])->assertOk();
    $product->delete();

    $response = $this->actingAs($buyer)->getJson('/api/v1/shop/cart')->assertOk();

    expect($response->json('data.items'))->toBeEmpty();
});

it('lets a seller publish a product and see it among their own', function (): void {
    $seller = User::factory()->create();
    $category = shopCategory();

    $created = $this->actingAs($seller)->postJson('/api/v1/shop/products', [
        'category_id' => $category->id,
        'title' => 'Лежанка',
        'price_amount' => 250000,
        'stock' => 2,
    ])->assertCreated();

    expect($created->json('data.status'))->toBe('published');

    $mine = $this->actingAs($seller)->getJson('/api/v1/shop/my-products')->assertOk();

    expect(array_column($mine->json('data'), 'title'))->toBe(['Лежанка']);
});

it('attaches a photo to a product and replaces it on re-upload', function (): void {
    Storage::fake('public');

    $seller = User::factory()->create();
    $product = shopProduct($seller, shopCategory());

    $first = $this->actingAs($seller)
        ->postJson("/api/v1/shop/products/{$product->id}/photo", [
            'photo' => UploadedFile::fake()->image('food.jpg'),
        ])
        ->assertOk();

    expect($first->json('data.photo_url'))->not->toBeNull();

    $second = $this->actingAs($seller)
        ->postJson("/api/v1/shop/products/{$product->id}/photo", [
            'photo' => UploadedFile::fake()->image('food-2.jpg'),
        ])
        ->assertOk();

    // Карточка держит одну картинку: вторая загрузка заменяет первую.
    expect($second->json('data.photo_url'))->not->toBe($first->json('data.photo_url'));
});

it('does not let a stranger attach a photo to someone else product', function (): void {
    Storage::fake('public');

    $product = shopProduct(User::factory()->create(), shopCategory());

    $this->actingAs(User::factory()->create())
        ->postJson("/api/v1/shop/products/{$product->id}/photo", [
            'photo' => UploadedFile::fake()->image('food.jpg'),
        ])
        ->assertForbidden();
});

it('does not let one seller edit another seller product', function (): void {
    $product = shopProduct(User::factory()->create(), shopCategory());

    $this->actingAs(User::factory()->create())->patchJson("/api/v1/shop/products/{$product->id}", [
        'category_id' => $product->category_id,
        'title' => 'Чужое',
        'price_amount' => 100,
        'stock' => 1,
    ])->assertForbidden();
});
