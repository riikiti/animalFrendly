<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Contracts\GeocodedAddress;
use App\Modules\Identity\Application\Contracts\GeocoderInterface;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;

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
