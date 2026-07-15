<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Profile\Infrastructure\Persistence\Eloquent\Models\Pet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

function uploadTestPhoto(string $petId, string $filename = 'cat.jpg'): TestResponse
{
    return test()->post("/api/v1/pets/{$petId}/photos", [
        'photo' => UploadedFile::fake()->image($filename, 200, 200),
    ]);
}

it('rejects unauthenticated access', function (): void {
    $pet = Pet::factory()->create();

    $this->postJson("/api/v1/pets/{$pet->id}/photos", [])->assertUnauthorized();
});

it('adds photos, lists them, changes the cover, and removes photos', function (): void {
    Storage::fake('public');

    $owner = User::factory()->create();
    $pet = Pet::factory()->create(['owner_id' => $owner->id]);

    Sanctum::actingAs($owner);

    $first = uploadTestPhoto($pet->id, 'first.jpg')->assertCreated();
    $firstId = $first->json('data.id');
    expect($first->json('data.is_primary'))->toBeTrue();

    $second = uploadTestPhoto($pet->id, 'second.jpg')->assertCreated();
    $secondId = $second->json('data.id');
    expect($second->json('data.is_primary'))->toBeFalse();

    $this->getJson("/api/v1/pets/{$pet->id}/photos")->assertOk()->assertJsonCount(2, 'data');

    $firstUrl = $first->json('data.url');
    $this->getJson('/api/v1/pets')->assertOk()->assertJsonPath('data.0.photo_url', $firstUrl);

    // Меняем обложку на второе фото.
    $secondUrl = $second->json('data.url');
    $this->postJson("/api/v1/pets/{$pet->id}/photos/{$secondId}/cover")
        ->assertOk()
        ->assertJsonPath('data.is_primary', true);
    $this->getJson('/api/v1/pets')->assertOk()->assertJsonPath('data.0.photo_url', $secondUrl);

    // Удаляем текущую обложку (второе фото) — первое становится обложкой автоматически.
    $this->deleteJson("/api/v1/pets/{$pet->id}/photos/{$secondId}")->assertOk();
    $this->getJson('/api/v1/pets')->assertOk()->assertJsonPath('data.0.photo_url', $firstUrl);

    // Удаляем последнее оставшееся фото — обложка сбрасывается.
    $this->deleteJson("/api/v1/pets/{$pet->id}/photos/{$firstId}")->assertOk();
    $this->getJson('/api/v1/pets')->assertOk()->assertJsonPath('data.0.photo_url', null);
    $this->getJson("/api/v1/pets/{$pet->id}/photos")->assertOk()->assertJsonCount(0, 'data');
});

it('rejects adding a photo for a pet owned by someone else', function (): void {
    Storage::fake('public');

    $pet = Pet::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    uploadTestPhoto($pet->id)->assertForbidden();
});

it('rejects a non-image file', function (): void {
    Storage::fake('public');

    $owner = User::factory()->create();
    $pet = Pet::factory()->create(['owner_id' => $owner->id]);
    Sanctum::actingAs($owner);

    $this->post("/api/v1/pets/{$pet->id}/photos", [
        'photo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
    ])->assertUnprocessable();
});

it('rejects adding more than the photo limit', function (): void {
    Storage::fake('public');

    $owner = User::factory()->create();
    $pet = Pet::factory()->create(['owner_id' => $owner->id]);
    Sanctum::actingAs($owner);

    for ($i = 0; $i < 6; $i++) {
        uploadTestPhoto($pet->id, "photo{$i}.jpg")->assertCreated();
    }

    uploadTestPhoto($pet->id, 'photo7.jpg')->assertUnprocessable();
});
