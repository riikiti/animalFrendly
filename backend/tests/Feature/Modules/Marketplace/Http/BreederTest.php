<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use Laravel\Sanctum\Sanctum;

it('registers a breeder profile as pending verification', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/v1/breeders');

    $response->assertCreated()->assertJsonPath('data.verification_status', 'pending');

    $meResponse = $this->getJson('/api/v1/breeders/me');
    $meResponse->assertOk()->assertJsonPath('data.verification_status', 'pending');
});

it('returns null when the user has no breeder profile', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/breeders/me')->assertOk()->assertJsonPath('data', null);
});

it('rejects listing pending breeder verifications from a non-moderator', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/breeders/pending-verification')->assertForbidden();
});

it('lists pending breeder verifications for moderators and verifies one', function (): void {
    Sanctum::actingAs(User::factory()->create());
    $breederId = $this->postJson('/api/v1/breeders')->json('data.id');

    $moderator = User::factory()->create(['account_type' => 'moderator']);
    Sanctum::actingAs($moderator);

    $pendingResponse = $this->getJson('/api/v1/breeders/pending-verification');
    $pendingResponse->assertOk();
    expect(collect($pendingResponse->json('data'))->pluck('id'))->toContain($breederId);

    $verifyResponse = $this->postJson("/api/v1/breeders/{$breederId}/verify", ['approve' => true]);
    $verifyResponse->assertOk()->assertJsonPath('data.verification_status', 'verified');

    $pendingAfter = $this->getJson('/api/v1/breeders/pending-verification');
    expect(collect($pendingAfter->json('data'))->pluck('id'))->not->toContain($breederId);
});

it('rejects verifying a breeder from a non-moderator', function (): void {
    Sanctum::actingAs(User::factory()->create());
    $breederId = $this->postJson('/api/v1/breeders')->json('data.id');

    Sanctum::actingAs(User::factory()->create());
    $this->postJson("/api/v1/breeders/{$breederId}/verify", ['approve' => true])->assertForbidden();
});
