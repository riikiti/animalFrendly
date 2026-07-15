<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Profile\Infrastructure\Persistence\Eloquent\Models\Pet;
use Laravel\Sanctum\Sanctum;

it('creates notifications for both owners on a match, and for the recipient on a new message', function (): void {
    $ownerA = User::factory()->create();
    $petA = Pet::factory()->create(['owner_id' => $ownerA->id]);

    $ownerB = User::factory()->create();
    $petB = Pet::factory()->create(['owner_id' => $ownerB->id]);

    Sanctum::actingAs($ownerB);
    $this->postJson("/api/v1/pets/{$petB->id}/swipes", [
        'target_pet_id' => $petA->id,
        'action' => 'like',
    ]);

    Sanctum::actingAs($ownerA);
    $response = $this->postJson("/api/v1/pets/{$petA->id}/swipes", [
        'target_pet_id' => $petB->id,
        'action' => 'like',
    ]);
    $matchId = $response->json('match.id');

    Sanctum::actingAs($ownerA);
    $this->getJson('/api/v1/notifications')->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'new_match');

    Sanctum::actingAs($ownerB);
    $this->getJson('/api/v1/notifications')->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'new_match');

    $conversationId = $this->getJson("/api/v1/matches/{$matchId}/conversation")->json('data.id');
    $this->postJson("/api/v1/conversations/{$conversationId}/messages", ['body' => 'Привет!'])->assertCreated();

    Sanctum::actingAs($ownerA);
    $this->getJson('/api/v1/notifications')->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.type', 'new_message');
});
