<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use Laravel\Sanctum\Sanctum;

it('rejects unauthenticated report submission', function (): void {
    $this->postJson('/api/v1/reports', [])->assertUnauthorized();
});

it('lets any authenticated user submit a report', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/v1/reports', [
        'target_type' => 'pet',
        'target_id' => 'some-pet-id',
        'reason' => 'spam',
        'comment' => 'Похоже на спам',
    ]);

    $response->assertCreated()->assertJsonPath('data.status', 'pending');
});

it('rejects a non-staff user from listing or acting on reports', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/reports', [
        'target_type' => 'pet',
        'target_id' => 'x',
        'reason' => 'spam',
    ])->assertCreated();

    $this->getJson('/api/v1/moderation/reports')->assertForbidden();
});

it('lets a moderator list, review, and dismiss reports', function (): void {
    $reporter = User::factory()->create();
    Sanctum::actingAs($reporter);

    $first = $this->postJson('/api/v1/reports', [
        'target_type' => 'pet', 'target_id' => 'pet-1', 'reason' => 'spam',
    ])->json('data.id');

    $second = $this->postJson('/api/v1/reports', [
        'target_type' => 'listing', 'target_id' => 'listing-1', 'reason' => 'scam',
    ])->json('data.id');

    $moderator = User::factory()->create(['account_type' => 'moderator']);
    Sanctum::actingAs($moderator);

    $this->getJson('/api/v1/moderation/reports')->assertOk()->assertJsonCount(2, 'data');

    $this->postJson("/api/v1/moderation/reports/{$first}/review")
        ->assertOk()
        ->assertJsonPath('data.status', 'reviewed')
        ->assertJsonPath('data.reviewed_by', $moderator->id);

    $this->postJson("/api/v1/moderation/reports/{$second}/dismiss")
        ->assertOk()
        ->assertJsonPath('data.status', 'dismissed');

    $this->getJson('/api/v1/moderation/reports')->assertOk()->assertJsonCount(0, 'data');
});
