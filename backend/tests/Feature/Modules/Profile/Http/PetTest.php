<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Breed;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Species;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Search\Infrastructure\Jobs\IndexPetJob;
use App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Models\Subscription as EloquentSubscription;
use App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Models\SubscriptionPlan as EloquentSubscriptionPlan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

it('rejects unauthenticated access', function (): void {
    $this->getJson('/api/v1/pets')->assertUnauthorized();
    $this->postJson('/api/v1/pets', [])->assertUnauthorized();
});

it('creates a pet and lists only the owner pets', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);
    $labrador = Breed::query()->create([
        'species_id' => $dog->id,
        'slug' => 'labrador',
        'name_ru' => 'Лабрадор',
        'is_active' => true,
    ]);

    Sanctum::actingAs($owner);

    $response = $this->postJson('/api/v1/pets', [
        'species_id' => $dog->id,
        'breed_id' => $labrador->id,
        'name' => 'Рекс',
        'sex' => 'male',
        'purpose' => 'social',
        'is_vaccinated' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Рекс')
        ->assertJsonPath('data.owner_id', $owner->id)
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.social_tags', []);

    $this->getJson('/api/v1/pets')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Рекс');

    Sanctum::actingAs($other);

    $this->getJson('/api/v1/pets')->assertOk()->assertJsonCount(0, 'data');
});

it('rejects a breed from a different species', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);
    $cat = Species::query()->create(['slug' => 'cat', 'name_ru' => 'Кошка', 'is_active' => true]);
    $siamese = Breed::query()->create([
        'species_id' => $cat->id,
        'slug' => 'siamese',
        'name_ru' => 'Сиамская',
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/pets', [
        'species_id' => $dog->id,
        'breed_id' => $siamese->id,
        'name' => 'Рекс',
        'sex' => 'male',
        'purpose' => 'social',
    ]);

    $response->assertUnprocessable();
});

it('rejects for_sale as a self-serve purpose', function (): void {
    Sanctum::actingAs(User::factory()->create());
    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);

    $response = $this->postJson('/api/v1/pets', [
        'species_id' => $dog->id,
        'name' => 'Рекс',
        'sex' => 'male',
        'purpose' => 'for_sale',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('purpose');
});

it('accepts and returns social tags', function (): void {
    Sanctum::actingAs(User::factory()->create());
    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);

    $response = $this->postJson('/api/v1/pets', [
        'species_id' => $dog->id,
        'name' => 'Рекс',
        'sex' => 'male',
        'purpose' => 'social',
        'social_tags' => ['walks', 'friendship'],
    ]);

    $response->assertCreated()->assertJsonPath('data.social_tags', ['walks', 'friendship']);
});

it('rejects an unknown social tag', function (): void {
    Sanctum::actingAs(User::factory()->create());
    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);

    $response = $this->postJson('/api/v1/pets', [
        'species_id' => $dog->id,
        'name' => 'Рекс',
        'sex' => 'male',
        'purpose' => 'social',
        'social_tags' => ['not_a_real_tag'],
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('social_tags.0');
});

it('rejects a second pet without an active subscription', function (): void {
    $owner = User::factory()->create();
    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);

    Sanctum::actingAs($owner);

    $this->postJson('/api/v1/pets', [
        'species_id' => $dog->id,
        'name' => 'Рекс',
        'sex' => 'male',
        'purpose' => 'social',
    ])->assertCreated();

    $response = $this->postJson('/api/v1/pets', [
        'species_id' => $dog->id,
        'name' => 'Барсик',
        'sex' => 'male',
        'purpose' => 'social',
    ]);

    $response->assertStatus(402)->assertJsonPath('error_code', 'pet_limit_exceeded');
});

it('allows a second pet with an active subscription', function (): void {
    $owner = User::factory()->create();
    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);

    Sanctum::actingAs($owner);

    $this->postJson('/api/v1/pets', [
        'species_id' => $dog->id,
        'name' => 'Рекс',
        'sex' => 'male',
        'purpose' => 'social',
    ])->assertCreated();

    $plan = EloquentSubscriptionPlan::query()->create([
        'slug' => 'plus',
        'name_ru' => 'Plus',
        'price_amount' => 29_900,
        'currency' => 'RUB',
        'period' => 'month',
        'features' => [],
        'is_active' => true,
    ]);

    EloquentSubscription::query()->create([
        'id' => (string) Str::ulid(),
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'started_at' => now(),
        'current_period_ends_at' => now()->addMonth(),
        'auto_renew' => true,
    ]);

    $response = $this->postJson('/api/v1/pets', [
        'species_id' => $dog->id,
        'name' => 'Барсик',
        'sex' => 'male',
        'purpose' => 'social',
    ]);

    $response->assertCreated()->assertJsonPath('data.name', 'Барсик');
});

it('queues search reindexing after a pet is created', function (): void {
    Queue::fake();
    Sanctum::actingAs(User::factory()->create());
    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);

    $this->postJson('/api/v1/pets', [
        'species_id' => $dog->id,
        'name' => 'Рекс',
        'sex' => 'male',
        'purpose' => 'social',
    ])->assertCreated();

    Queue::assertPushed(IndexPetJob::class);
});
