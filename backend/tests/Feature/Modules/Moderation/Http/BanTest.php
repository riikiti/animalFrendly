<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use Laravel\Sanctum\Sanctum;

it('rejects banning for a non-staff user', function (): void {
    $target = User::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/v1/moderation/users/{$target->id}/ban")->assertForbidden();
});

it('bans a user, revoking their tokens immediately, then unbans them', function (): void {
    $target = User::factory()->create();
    $token = $target->createToken('api')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/me')
        ->assertOk();

    $moderator = User::factory()->create(['account_type' => 'moderator']);
    Sanctum::actingAs($moderator);

    $this->postJson("/api/v1/moderation/users/{$target->id}/ban")
        ->assertOk()
        ->assertJsonPath('data.status', 'blocked');

    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();

    Sanctum::actingAs($moderator);
    $this->postJson("/api/v1/moderation/users/{$target->id}/unban")
        ->assertOk()
        ->assertJsonPath('data.status', 'active');
});

it('rejects login for a blocked user even with the right password', function (): void {
    $target = User::factory()->create(['phone' => '+79261230000']);

    $moderator = User::factory()->create(['account_type' => 'moderator']);
    Sanctum::actingAs($moderator);
    $this->postJson("/api/v1/moderation/users/{$target->id}/ban")->assertOk();

    $this->postJson('/api/v1/auth/login', [
        'phone' => '+79261230000',
        'password' => 'password',
    ])->assertForbidden();
});
