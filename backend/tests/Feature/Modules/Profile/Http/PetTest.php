<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Breed;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Species;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Search\Infrastructure\Jobs\IndexPetJob;
use Illuminate\Support\Facades\Queue;
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
        ->assertJsonPath('data.status', 'active');

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
