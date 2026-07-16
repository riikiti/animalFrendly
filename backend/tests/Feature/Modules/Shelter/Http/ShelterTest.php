<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Species;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use Laravel\Sanctum\Sanctum;

function publishVerifiedShelterAnimal(User $shelterOwner, int $speciesId): string
{
    Sanctum::actingAs($shelterOwner);
    $shelterId = test()->postJson('/api/v1/shelters', ['legal_name' => 'Добрые лапы'])
        ->json('data.id');

    $moderator = User::factory()->create(['account_type' => 'moderator']);
    Sanctum::actingAs($moderator);
    test()->postJson("/api/v1/shelters/{$shelterId}/verify", ['approve' => true])->assertOk();

    Sanctum::actingAs($shelterOwner);
    $shelterAnimalId = test()->postJson("/api/v1/shelters/{$shelterId}/animals", [
        'species_id' => $speciesId,
        'name' => 'Рыжик',
        'sex' => 'male',
        'is_vaccinated' => true,
    ])->json('data.id');

    return $shelterAnimalId;
}

it('rejects publishing an animal before the shelter is verified', function (): void {
    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);
    $owner = User::factory()->create();

    Sanctum::actingAs($owner);
    $shelterId = $this->postJson('/api/v1/shelters', ['legal_name' => 'Добрые лапы'])->json('data.id');

    $response = $this->postJson("/api/v1/shelters/{$shelterId}/animals", [
        'species_id' => $dog->id,
        'name' => 'Рыжик',
        'sex' => 'male',
    ]);

    $response->assertUnprocessable();
});

it('rejects verification from a non-moderator', function (): void {
    $owner = User::factory()->create();
    Sanctum::actingAs($owner);
    $shelterId = $this->postJson('/api/v1/shelters', ['legal_name' => 'Добрые лапы'])->json('data.id');

    Sanctum::actingAs(User::factory()->create());
    $this->postJson("/api/v1/shelters/{$shelterId}/verify", ['approve' => true])->assertForbidden();
});

it('publishes an animal once the shelter is verified and lists it as available', function (): void {
    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);
    $owner = User::factory()->create();

    $shelterAnimalId = publishVerifiedShelterAnimal($owner, $dog->id);

    Sanctum::actingAs(User::factory()->create());
    $response = $this->getJson('/api/v1/shelter-animals');

    $response->assertOk();
    $listed = collect($response->json('data'))->firstWhere('id', $shelterAnimalId);

    expect($listed)->not->toBeNull()
        ->and($listed['pet']['name'])->toBe('Рыжик')
        ->and($listed['pet']['species_id'])->toBe($dog->id);
});

it('runs the full adoption flow: request → approve → reserved → conversation created', function (): void {
    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);
    $shelterOwner = User::factory()->create();
    $shelterAnimalId = publishVerifiedShelterAnimal($shelterOwner, $dog->id);

    $adopter = User::factory()->create();
    Sanctum::actingAs($adopter);
    $requestResponse = $this->postJson("/api/v1/shelter-animals/{$shelterAnimalId}/adoption-requests", [
        'message' => 'Живу в своём доме, есть опыт',
    ]);
    $requestResponse->assertCreated()->assertJsonPath('data.status', 'pending');
    $adoptionRequestId = $requestResponse->json('data.id');

    Sanctum::actingAs($shelterOwner);
    $shelterId = $this->getJson('/api/v1/shelters/me')->json('data.id');
    $this->getJson("/api/v1/shelters/{$shelterId}/adoption-requests/pending")
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $decideResponse = $this->postJson("/api/v1/adoption-requests/{$adoptionRequestId}/decide", ['approve' => true]);
    $decideResponse->assertOk()->assertJsonPath('data.status', 'approved');

    $availableResponse = $this->getJson('/api/v1/shelter-animals');
    expect(collect($availableResponse->json('data'))->pluck('id'))->not->toContain($shelterAnimalId);

    $conversationResponse = $this->getJson("/api/v1/adoption-requests/{$adoptionRequestId}/conversation");
    $conversationResponse->assertOk()->assertJsonPath('data.adoption_request_id', $adoptionRequestId);
    $conversationId = $conversationResponse->json('data.id');

    $this->postJson("/api/v1/conversations/{$conversationId}/messages", ['body' => 'Здравствуйте!'])
        ->assertCreated();

    Sanctum::actingAs($adopter);
    $this->getJson("/api/v1/conversations/{$conversationId}/messages")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('lets the requester cancel a pending request', function (): void {
    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);
    $shelterOwner = User::factory()->create();
    $shelterAnimalId = publishVerifiedShelterAnimal($shelterOwner, $dog->id);

    $adopter = User::factory()->create();
    Sanctum::actingAs($adopter);
    $adoptionRequestId = $this->postJson("/api/v1/shelter-animals/{$shelterAnimalId}/adoption-requests", [])
        ->json('data.id');

    $this->postJson("/api/v1/adoption-requests/{$adoptionRequestId}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});

it('rejects a decision from someone other than the shelter owner', function (): void {
    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);
    $shelterOwner = User::factory()->create();
    $shelterAnimalId = publishVerifiedShelterAnimal($shelterOwner, $dog->id);

    Sanctum::actingAs(User::factory()->create());
    $adoptionRequestId = $this->postJson("/api/v1/shelter-animals/{$shelterAnimalId}/adoption-requests", [])
        ->json('data.id');

    Sanctum::actingAs(User::factory()->create());
    $this->postJson("/api/v1/adoption-requests/{$adoptionRequestId}/decide", ['approve' => true])
        ->assertForbidden();
});

it("lists the shelter owner's own animals", function (): void {
    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);
    $shelterOwner = User::factory()->create();
    $shelterAnimalId = publishVerifiedShelterAnimal($shelterOwner, $dog->id);

    Sanctum::actingAs($shelterOwner);
    $shelterId = $this->getJson('/api/v1/shelters/me')->json('data.id');

    $response = $this->getJson("/api/v1/shelters/{$shelterId}/animals");

    $response->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $shelterAnimalId)
        ->assertJsonPath('data.0.pet.name', 'Рыжик');
});

it("rejects listing another shelter's animals", function (): void {
    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);
    $shelterOwner = User::factory()->create();
    publishVerifiedShelterAnimal($shelterOwner, $dog->id);

    Sanctum::actingAs($shelterOwner);
    $shelterId = $this->getJson('/api/v1/shelters/me')->json('data.id');

    Sanctum::actingAs(User::factory()->create());
    $this->getJson("/api/v1/shelters/{$shelterId}/animals")->assertForbidden();
});

it('lists shelters pending verification for moderators', function (): void {
    $verifiedOwner = User::factory()->create();
    Sanctum::actingAs($verifiedOwner);
    $verifiedShelterId = $this->postJson('/api/v1/shelters', ['legal_name' => 'Верифицированный приют'])
        ->json('data.id');

    $pendingOwner = User::factory()->create();
    Sanctum::actingAs($pendingOwner);
    $pendingShelterId = $this->postJson('/api/v1/shelters', ['legal_name' => 'Приют на рассмотрении'])
        ->json('data.id');

    $moderator = User::factory()->create(['account_type' => 'moderator']);
    Sanctum::actingAs($moderator);
    $this->postJson("/api/v1/shelters/{$verifiedShelterId}/verify", ['approve' => true])->assertOk();

    $response = $this->getJson('/api/v1/shelters/pending-verification');

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($pendingShelterId)
        ->and($ids)->not->toContain($verifiedShelterId);
});

it('rejects listing pending shelter verifications from a non-moderator', function (): void {
    Sanctum::actingAs(User::factory()->create());
    $this->getJson('/api/v1/shelters/pending-verification')->assertForbidden();
});

it('rejects access to the adoption conversation for a stranger', function (): void {
    $dog = Species::query()->create(['slug' => 'dog', 'name_ru' => 'Собака', 'is_active' => true]);
    $shelterOwner = User::factory()->create();
    $shelterAnimalId = publishVerifiedShelterAnimal($shelterOwner, $dog->id);

    $adopter = User::factory()->create();
    Sanctum::actingAs($adopter);
    $adoptionRequestId = $this->postJson("/api/v1/shelter-animals/{$shelterAnimalId}/adoption-requests", [])
        ->json('data.id');

    Sanctum::actingAs($shelterOwner);
    $this->postJson("/api/v1/adoption-requests/{$adoptionRequestId}/decide", ['approve' => true])->assertOk();

    Sanctum::actingAs(User::factory()->create());
    $this->getJson("/api/v1/adoption-requests/{$adoptionRequestId}/conversation")->assertForbidden();
});
