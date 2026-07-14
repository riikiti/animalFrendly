<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Species;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use Laravel\Sanctum\Sanctum;

function createTestListing(int $priceAmount = 100_000): array
{
    $dog = Species::query()->firstOrCreate(['slug' => 'dog'], ['name_ru' => 'Собака', 'is_active' => true]);
    $seller = User::factory()->create();

    Sanctum::actingAs($seller);
    $response = test()->postJson('/api/v1/listings', [
        'species_id' => $dog->id,
        'name' => 'Барон',
        'sex' => 'male',
        'is_vaccinated' => true,
        'price_amount' => $priceAmount,
    ]);

    return ['seller' => $seller, 'listingId' => $response->json('data.id')];
}

it('creates a draft listing with the pet embedded', function (): void {
    ['listingId' => $listingId] = createTestListing();

    expect($listingId)->not->toBeNull();
});

it('publishes a listing and lists it publicly', function (): void {
    ['seller' => $seller, 'listingId' => $listingId] = createTestListing();

    Sanctum::actingAs($seller);
    $this->postJson("/api/v1/listings/{$listingId}/publish")
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    Sanctum::actingAs(User::factory()->create());
    $listed = collect($this->getJson('/api/v1/listings')->json('data'))->firstWhere('id', $listingId);

    expect($listed)->not->toBeNull()
        ->and($listed['pet']['name'])->toBe('Барон');
});

it('does not list drafts publicly', function (): void {
    createTestListing();

    Sanctum::actingAs(User::factory()->create());
    $response = $this->getJson('/api/v1/listings');

    expect($response->json('data'))->toBe([]);
});

it('rejects publishing someone else’s listing', function (): void {
    ['listingId' => $listingId] = createTestListing();

    Sanctum::actingAs(User::factory()->create());
    $this->postJson("/api/v1/listings/{$listingId}/publish")->assertForbidden();
});

it('archives a draft listing', function (): void {
    ['seller' => $seller, 'listingId' => $listingId] = createTestListing();

    Sanctum::actingAs($seller);
    $this->postJson("/api/v1/listings/{$listingId}/archive")
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');
});

it('lists the seller’s own listings regardless of status', function (): void {
    ['seller' => $seller] = createTestListing();

    Sanctum::actingAs($seller);
    $this->getJson('/api/v1/listings/me')->assertOk()->assertJsonCount(1, 'data');
});
