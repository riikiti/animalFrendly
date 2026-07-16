<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Contracts\GeocodedAddress;
use App\Modules\Identity\Application\Contracts\GeocoderInterface;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

it('saves the raw address without coordinates when the geocoder is unavailable (null fallback)', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/v1/auth/me', ['address' => 'Непонятный адрес']);

    $response->assertOk()->assertJsonPath('city', null);

    expect($user->fresh()->address)->toBe('Непонятный адрес')
        ->and($user->fresh()->latitude)->toBeNull();
});

it('resolves city and coordinates through the geocoder when it succeeds', function (): void {
    $geocoder = Mockery::mock(GeocoderInterface::class);
    $geocoder->shouldReceive('geocode')->once()->with('Москва, ул. Тверская, 1')->andReturn(
        new GeocodedAddress('Москва, Тверская улица, 1', 'Москва', 55.755, 37.617),
    );
    app()->bind(GeocoderInterface::class, fn () => $geocoder);

    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/v1/auth/me', ['address' => 'Москва, ул. Тверская, 1']);

    $response->assertOk()->assertJsonPath('city', 'Москва');

    expect($user->fresh()->latitude)->toBe(55.755)
        ->and($user->fresh()->longitude)->toBe(37.617);
});

it('rejects unauthenticated profile updates', function (): void {
    $this->patchJson('/api/v1/auth/me', ['address' => 'Любой адрес'])->assertUnauthorized();
});

it('sets the name', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/v1/auth/me', ['name' => 'Иван Иванов']);

    $response->assertOk()->assertJsonPath('name', 'Иван Иванов');
    expect($user->fresh()->name)->toBe('Иван Иванов');
});

it('uploads an avatar', function (): void {
    Storage::fake('public');

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/auth/me/avatar', [
        'photo' => UploadedFile::fake()->image('me.jpg', 200, 200),
    ]);

    $response->assertOk();
    expect($response->json('avatar_url'))->not->toBeNull()
        ->and($user->fresh()->avatar_url)->not->toBeNull();
});

it('rejects an unauthenticated avatar upload', function (): void {
    $this->postJson('/api/v1/auth/me/avatar', [])->assertUnauthorized();
});
