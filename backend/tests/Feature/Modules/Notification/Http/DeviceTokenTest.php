<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use Laravel\Sanctum\Sanctum;

it('rejects unauthenticated access', function (): void {
    $this->postJson('/api/v1/notifications/device-tokens', [])->assertUnauthorized();
});

it('registers and unregisters a device token', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/notifications/device-tokens', [
        'platform' => 'android',
        'fcm_token' => 'token-123',
    ])->assertCreated();

    $this->assertDatabaseHas('device_tokens', ['fcm_token' => 'token-123', 'platform' => 'android']);

    $this->deleteJson('/api/v1/notifications/device-tokens/token-123')->assertOk();

    $this->assertDatabaseMissing('device_tokens', ['fcm_token' => 'token-123']);
});

it('rejects an unknown platform', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/notifications/device-tokens', [
        'platform' => 'windows',
        'fcm_token' => 'token-123',
    ])->assertUnprocessable();
});

it('re-registering the same token updates last_used_at instead of duplicating', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $payload = ['platform' => 'ios', 'fcm_token' => 'token-abc'];

    $this->postJson('/api/v1/notifications/device-tokens', $payload)->assertCreated();
    $this->postJson('/api/v1/notifications/device-tokens', $payload)->assertCreated();

    $this->assertDatabaseCount('device_tokens', 1);
});
