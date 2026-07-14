<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Breed;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Species;

it('lists active species ordered by name', function (): void {
    Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);
    Species::query()->create(['slug' => 'cat', 'name_ru' => 'Кошка', 'is_active' => true]);
    Species::query()->create(['slug' => 'extinct', 'name_ru' => 'Скрытый вид', 'is_active' => false]);

    $response = $this->getJson('/api/v1/catalog/species');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Кошка')
        ->assertJsonPath('data.1.name', 'Собака');
});

it('lists active breeds for a given species', function (): void {
    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);
    Breed::query()->create(['species_id' => $dog->id, 'slug' => 'labrador', 'name_ru' => 'Лабрадор', 'is_active' => true]);
    Breed::query()->create(['species_id' => $dog->id, 'slug' => 'husky', 'name_ru' => 'Хаски', 'is_active' => false]);

    $response = $this->getJson('/api/v1/catalog/species/dog/breeds');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Лабрадор');
});

it('returns 404 for an unknown species slug', function (): void {
    $this->getJson('/api/v1/catalog/species/dragon/breeds')->assertNotFound();
});
