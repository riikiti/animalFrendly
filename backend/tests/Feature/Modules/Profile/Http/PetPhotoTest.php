<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Profile\Infrastructure\Persistence\Eloquent\Models\Pet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

it('rejects unauthenticated access', function (): void {
    $pet = Pet::factory()->create();

    $this->postJson("/api/v1/pets/{$pet->id}/photo", [])->assertUnauthorized();
});

it('sets and removes a photo for the owner pet', function (): void {
    Storage::fake('local');

    $owner = User::factory()->create();
    $pet = Pet::factory()->create(['owner_id' => $owner->id]);

    Sanctum::actingAs($owner);

    $response = $this->post("/api/v1/pets/{$pet->id}/photo", [
        'photo' => UploadedFile::fake()->image('cat.jpg', 200, 200),
    ]);

    $response->assertOk();
    $photoUrl = $response->json('data.photo_url');
    expect($photoUrl)->not->toBeNull();

    $this->getJson('/api/v1/pets')
        ->assertOk()
        ->assertJsonPath('data.0.photo_url', $photoUrl);

    $this->deleteJson("/api/v1/pets/{$pet->id}/photo")
        ->assertOk()
        ->assertJsonPath('data.photo_url', null);
});

it('rejects setting a photo for a pet owned by someone else', function (): void {
    Storage::fake('local');

    $pet = Pet::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->post("/api/v1/pets/{$pet->id}/photo", [
        'photo' => UploadedFile::fake()->image('cat.jpg'),
    ])->assertForbidden();
});

it('rejects a non-image file', function (): void {
    Storage::fake('local');

    $owner = User::factory()->create();
    $pet = Pet::factory()->create(['owner_id' => $owner->id]);
    Sanctum::actingAs($owner);

    $this->post("/api/v1/pets/{$pet->id}/photo", [
        'photo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
    ])->assertUnprocessable();
});
