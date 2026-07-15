<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Species;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use Laravel\Sanctum\Sanctum;

it('lets the buyer review the seller after the order is completed', function (): void {
    ['seller' => $seller, 'buyer' => $buyer, 'orderId' => $orderId, 'yookassaPaymentId' => $yookassaPaymentId] = purchaseTestListing();
    sendSucceededWebhook($yookassaPaymentId);

    Sanctum::actingAs($buyer);
    $this->postJson("/api/v1/orders/{$orderId}/confirm")->assertOk();
    Sanctum::actingAs($seller);
    $this->postJson("/api/v1/orders/{$orderId}/confirm")->assertOk()->assertJsonPath('data.status', 'completed');

    Sanctum::actingAs($buyer);
    $this->postJson('/api/v1/reviews', ['order_id' => $orderId, 'rating' => 5, 'comment' => 'Отлично'])
        ->assertCreated()
        ->assertJsonPath('data.rating', 5);

    $rating = $this->getJson("/api/v1/users/{$seller->id}/rating")->json('data');
    // PHP сериализует float без дробной части как целое число в JSON (5.0 → "5"), поэтому
    // json_decode возвращает int — сравниваем как число, а не по строгому типу.
    expect((float) $rating['average'])->toBe(5.0)->and($rating['count'])->toBe(1);

    // Повторный отзыв по тому же заказу отклоняется.
    $this->postJson('/api/v1/reviews', ['order_id' => $orderId, 'rating' => 4])->assertUnprocessable();
});

it('rejects a review before the order is completed', function (): void {
    ['buyer' => $buyer, 'orderId' => $orderId] = purchaseTestListing();

    Sanctum::actingAs($buyer);
    $this->postJson('/api/v1/reviews', ['order_id' => $orderId, 'rating' => 5])->assertForbidden();
});

it('lets the adopter review the shelter after the request is approved', function (): void {
    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);
    $shelterOwner = User::factory()->create();
    $shelterAnimalId = publishVerifiedShelterAnimal($shelterOwner, $dog->id);

    $adopter = User::factory()->create();
    Sanctum::actingAs($adopter);
    $adoptionRequestId = $this->postJson("/api/v1/shelter-animals/{$shelterAnimalId}/adoption-requests", [])
        ->json('data.id');

    Sanctum::actingAs($shelterOwner);
    $this->postJson("/api/v1/adoption-requests/{$adoptionRequestId}/decide", ['approve' => true])->assertOk();

    Sanctum::actingAs($adopter);
    $this->postJson('/api/v1/reviews', ['adoption_request_id' => $adoptionRequestId, 'rating' => 5])
        ->assertCreated();

    $rating = $this->getJson("/api/v1/users/{$shelterOwner->id}/rating")->json('data');
    expect($rating['count'])->toBe(1);
});
