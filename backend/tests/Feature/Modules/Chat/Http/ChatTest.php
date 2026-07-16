<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Species;
use App\Modules\Chat\Infrastructure\Broadcasting\MessageBroadcast;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Profile\Infrastructure\Persistence\Eloquent\Models\Pet;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

function createVerifiedShelterWithAnimal(): array
{
    $dog = Species::query()->firstOrCreate(['slug' => 'dog'], ['name_ru' => 'Собака', 'is_active' => true]);
    $shelterOwner = User::factory()->create();

    Sanctum::actingAs($shelterOwner);
    $shelterId = test()->postJson('/api/v1/shelters', ['legal_name' => 'Добрые лапы'])
        ->json('data.id');

    $moderator = User::factory()->create(['account_type' => 'moderator']);
    Sanctum::actingAs($moderator);
    test()->postJson("/api/v1/shelters/{$shelterId}/verify", ['approve' => true])->assertOk();

    Sanctum::actingAs($shelterOwner);
    $shelterAnimalId = test()->postJson("/api/v1/shelters/{$shelterId}/animals", [
        'species_id' => $dog->id,
        'name' => 'Рыжик',
        'sex' => 'male',
        'is_vaccinated' => true,
    ])->json('data.id');

    return [$shelterOwner, $shelterId, $shelterAnimalId];
}

function createMutualMatch(): array
{
    $ownerA = User::factory()->create();
    $petA = Pet::factory()->create(['owner_id' => $ownerA->id]);

    $ownerB = User::factory()->create();
    $petB = Pet::factory()->create(['owner_id' => $ownerB->id]);

    Sanctum::actingAs($ownerB);
    test()->postJson("/api/v1/pets/{$petB->id}/swipes", [
        'target_pet_id' => $petA->id,
        'action' => 'like',
    ]);

    Sanctum::actingAs($ownerA);
    $response = test()->postJson("/api/v1/pets/{$petA->id}/swipes", [
        'target_pet_id' => $petB->id,
        'action' => 'like',
    ]);

    $matchId = $response->json('match.id');

    return [$ownerA, $ownerB, $matchId];
}

it('auto-creates a conversation when pets match (event-driven)', function (): void {
    [$ownerA, , $matchId] = createMutualMatch();

    Sanctum::actingAs($ownerA);
    $response = $this->getJson("/api/v1/matches/{$matchId}/conversation");

    $response->assertOk()->assertJsonPath('data.match_id', $matchId);
});

it('rejects access to the conversation for someone who is not a participant', function (): void {
    [, , $matchId] = createMutualMatch();

    Sanctum::actingAs(User::factory()->create());
    $this->getJson("/api/v1/matches/{$matchId}/conversation")->assertForbidden();
});

it('reveals the counterpart address to a match participant, never to strangers', function (): void {
    [$ownerA, $ownerB, $matchId] = createMutualMatch();
    $ownerB->forceFill([
        'address' => 'Санкт-Петербург, Невский проспект, 1',
        'latitude' => 59.93,
        'longitude' => 30.36,
    ])->save();

    Sanctum::actingAs($ownerA);
    $this->getJson("/api/v1/matches/{$matchId}/conversation")
        ->assertOk()
        ->assertJsonPath('data.counterpart_address', 'Санкт-Петербург, Невский проспект, 1')
        ->assertJsonPath('data.counterpart_location.lat', 59.93)
        ->assertJsonPath('data.counterpart_location.lng', 30.36);

    Sanctum::actingAs(User::factory()->create());
    $this->getJson("/api/v1/matches/{$matchId}/conversation")->assertForbidden();
});

it('lets both participants send and read messages', function (): void {
    [$ownerA, $ownerB, $matchId] = createMutualMatch();

    Sanctum::actingAs($ownerA);
    $conversationId = $this->getJson("/api/v1/matches/{$matchId}/conversation")->json('data.id');

    $this->postJson("/api/v1/conversations/{$conversationId}/messages", ['body' => 'Привет!'])
        ->assertCreated()
        ->assertJsonPath('data.body', 'Привет!')
        ->assertJsonPath('data.sender_id', $ownerA->id);

    Sanctum::actingAs($ownerB);
    $this->postJson("/api/v1/conversations/{$conversationId}/messages", ['body' => 'И тебе привет!'])
        ->assertCreated();

    $messages = $this->getJson("/api/v1/conversations/{$conversationId}/messages")->assertOk();
    $messages->assertJsonCount(2, 'data');
    expect($messages->json('data.0.body'))->toBe('Привет!')
        ->and($messages->json('data.1.body'))->toBe('И тебе привет!');
});

it('broadcasts a sent message to the conversation channel', function (): void {
    [$ownerA, , $matchId] = createMutualMatch();

    Sanctum::actingAs($ownerA);
    $conversationId = $this->getJson("/api/v1/matches/{$matchId}/conversation")->json('data.id');

    Event::fake([MessageBroadcast::class]);

    $this->postJson("/api/v1/conversations/{$conversationId}/messages", ['body' => 'В эфире!'])
        ->assertCreated();

    Event::assertDispatched(MessageBroadcast::class, fn (MessageBroadcast $event) => $event->conversationId === $conversationId
        && $event->senderId === $ownerA->id
        && $event->body === 'В эфире!');
});

it('rejects sending messages from a non-participant', function (): void {
    [$ownerA, , $matchId] = createMutualMatch();

    Sanctum::actingAs($ownerA);
    $conversationId = $this->getJson("/api/v1/matches/{$matchId}/conversation")->json('data.id');

    Sanctum::actingAs(User::factory()->create());
    $this->postJson("/api/v1/conversations/{$conversationId}/messages", ['body' => 'Хай'])
        ->assertForbidden();
});

it('lists the conversation for both participants via /conversations', function (): void {
    [$ownerA, $ownerB, $matchId] = createMutualMatch();

    Sanctum::actingAs($ownerA);
    $this->getJson("/api/v1/matches/{$matchId}/conversation")->assertOk();

    Sanctum::actingAs($ownerA);
    $this->getJson('/api/v1/conversations')->assertOk()->assertJsonCount(1, 'data');

    Sanctum::actingAs($ownerB);
    $this->getJson('/api/v1/conversations')->assertOk()->assertJsonCount(1, 'data');
});

it('creates a direct conversation with a shelter, attaching the animal card', function (): void {
    [$shelterOwner, $shelterId, $shelterAnimalId] = createVerifiedShelterWithAnimal();

    $visitor = User::factory()->create();
    Sanctum::actingAs($visitor);

    $response = $this->postJson("/api/v1/shelters/{$shelterId}/conversations", [
        'shelter_animal_id' => $shelterAnimalId,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.shelter_id', $shelterId)
        ->assertJsonPath('data.shelter_animal_id', $shelterAnimalId);

    $conversationId = $response->json('data.id');

    $this->postJson("/api/v1/conversations/{$conversationId}/messages", ['body' => 'Расскажите про Рыжика'])
        ->assertCreated();

    Sanctum::actingAs($shelterOwner);
    $this->postJson("/api/v1/conversations/{$conversationId}/messages", ['body' => 'Конечно!'])
        ->assertCreated();

    $messages = $this->getJson("/api/v1/conversations/{$conversationId}/messages")->assertOk();
    $messages->assertJsonCount(2, 'data');
});

it('reuses the same direct shelter conversation on repeated contact', function (): void {
    [, $shelterId] = createVerifiedShelterWithAnimal();

    $visitor = User::factory()->create();
    Sanctum::actingAs($visitor);

    $firstId = $this->postJson("/api/v1/shelters/{$shelterId}/conversations")->json('data.id');
    $secondId = $this->postJson("/api/v1/shelters/{$shelterId}/conversations")->json('data.id');

    expect($secondId)->toBe($firstId);
});

it('rejects sending messages in a direct shelter conversation from a stranger', function (): void {
    [, $shelterId] = createVerifiedShelterWithAnimal();

    $visitor = User::factory()->create();
    Sanctum::actingAs($visitor);
    $conversationId = $this->postJson("/api/v1/shelters/{$shelterId}/conversations")->json('data.id');

    Sanctum::actingAs(User::factory()->create());
    $this->postJson("/api/v1/conversations/{$conversationId}/messages", ['body' => 'Хай'])
        ->assertForbidden();
});

it('lists direct shelter conversations for both the visitor and the shelter owner', function (): void {
    [$shelterOwner, $shelterId] = createVerifiedShelterWithAnimal();

    $visitor = User::factory()->create(['name' => 'Мария']);
    Sanctum::actingAs($visitor);
    $this->postJson("/api/v1/shelters/{$shelterId}/conversations")->assertOk();

    $visitorConversations = $this->getJson('/api/v1/conversations')->assertOk();
    expect(collect($visitorConversations->json('data'))->pluck('shelter_id'))->toContain($shelterId);

    Sanctum::actingAs($shelterOwner);
    $ownerConversations = $this->getJson('/api/v1/conversations')->assertOk();
    $listed = collect($ownerConversations->json('data'))->firstWhere('shelter_id', $shelterId);

    expect($listed)->not->toBeNull()
        ->and($listed['counterpart_name'])->toBe('Мария');
});
