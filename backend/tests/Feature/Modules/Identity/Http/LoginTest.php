<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

it('logs in with correct credentials and returns an api token', function (): void {
    User::factory()->create([
        'phone' => '+79261234567',
        'password_hash' => Hash::make('correct-password'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'phone' => '+79261234567',
        'password' => 'correct-password',
    ]);

    $response->assertOk()->assertJsonStructure(['user' => ['id', 'phone'], 'token']);
});

it('rejects login with a wrong password', function (): void {
    User::factory()->create([
        'phone' => '+79261234567',
        'password_hash' => Hash::make('correct-password'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'phone' => '+79261234567',
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized();
});

it('returns the authenticated user from /me and revokes the token on logout', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('id', $user->id);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    expect(PersonalAccessToken::query()->count())->toBe(0);

    // RequestGuard в Laravel кэширует резолвнутого пользователя на всё время жизни
    // объекта guard'а, а он, в отличие от отдельных реальных HTTP-запросов, живёт
    // все симулированные запросы в рамках одного теста. Принудительно сбрасываем
    // guard, чтобы проверка отражала реальное удаление токена, а не устаревший
    // кэш внутри теста.
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});

it('rejects unauthenticated access to /me', function (): void {
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('rejects login for a blocked account', function (): void {
    User::factory()->create([
        'phone' => '+79261234567',
        'password_hash' => Hash::make('correct-password'),
        'status' => 'blocked',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'phone' => '+79261234567',
        'password' => 'correct-password',
    ]);

    $response->assertForbidden();
});
