<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * См. Chat/Http/BroadcastAuthTest.php — тот же driver-caching gotcha (BroadcastManager
 * кэширует резолвленный драйвер по имени соединения, поэтому channels.php нужно
 * переподключить уже после переключения на 'reverb').
 */
beforeEach(function (): void {
    config(['broadcasting.default' => 'reverb']);
    require base_path('routes/channels.php');
});

it('authorizes a user on their own private notification channel', function (): void {
    $user = User::factory()->create();

    Sanctum::actingAs($user);
    $this->postJson('/api/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => "private-user.{$user->id}",
    ])->assertOk();
});

it('rejects access to another user private notification channel', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    Sanctum::actingAs($intruder);
    $this->postJson('/api/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => "private-user.{$owner->id}",
    ])->assertForbidden();
});

it('rejects unauthenticated access to the user notification channel', function (): void {
    $this->postJson('/api/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => 'private-user.does-not-matter',
    ])->assertUnauthorized();
});
