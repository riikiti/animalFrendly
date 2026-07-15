<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Search\Application\Contracts\ListingSearchIndexInterface;
use App\Modules\Search\Application\Contracts\PetSearchIndexInterface;
use Illuminate\Support\Str;
use Tests\Support\FakeListingSearchIndex;
use Tests\Support\FakePetSearchIndex;

function petSearchDocument(array $overrides = []): array
{
    return array_merge([
        'id' => (string) Str::ulid(),
        'name' => 'Рекс',
        'species_id' => 1,
        'species_name' => 'Собака',
        'breed_id' => null,
        'breed_name' => null,
        'sex' => 'male',
        'purpose' => 'social',
        'city' => 'Москва',
        'is_vaccinated' => true,
        'is_boosted' => false,
        'photo_url' => null,
    ], $overrides);
}

function listingSearchDocument(array $overrides = []): array
{
    return array_merge([
        'id' => (string) Str::ulid(),
        'pet_name' => 'Барсик',
        'species_id' => 2,
        'species_name' => 'Кошка',
        'breed_id' => null,
        'breed_name' => null,
        'city' => 'Москва',
        'price_amount' => 30_000,
        'currency' => 'RUB',
        'photo_url' => null,
    ], $overrides);
}

it('filters pets by city', function (): void {
    $this->app->singleton(PetSearchIndexInterface::class, FakePetSearchIndex::class);
    $index = $this->app->make(PetSearchIndexInterface::class);
    $index->putDocument(petSearchDocument(['city' => 'Москва']));
    $index->putDocument(petSearchDocument(['city' => 'Казань']));

    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/search/pets?city=Москва');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.city'))->toBe('Москва');
});

it('filters listings by price range', function (): void {
    $this->app->singleton(ListingSearchIndexInterface::class, FakeListingSearchIndex::class);
    $index = $this->app->make(ListingSearchIndexInterface::class);
    $index->putDocument(listingSearchDocument(['price_amount' => 10_000]));
    $index->putDocument(listingSearchDocument(['price_amount' => 90_000]));

    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/search/listings?min_price_amount=50000');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.price_amount'))->toBe(90_000);
});

it('rejects unauthenticated search requests', function (): void {
    $this->getJson('/api/v1/search/pets')->assertUnauthorized();
    $this->getJson('/api/v1/search/listings')->assertUnauthorized();
});
