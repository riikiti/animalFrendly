<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;

it('registers a new user and returns an api token', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'phone' => '+7 926 123-45-67',
        'password' => 'correct-password',
        'password_confirmation' => 'correct-password',
        'account_type' => 'owner',
        'personal_data_consent' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('user.phone', '+79261234567')
        ->assertJsonPath('user.account_type', 'owner')
        ->assertJsonStructure(['user' => ['id', 'phone', 'account_type', 'status', 'created_at'], 'token']);

    expect(User::query()->where('phone', '+79261234567')->exists())->toBeTrue();
});

it('rejects registration without personal data consent', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'phone' => '+79261234567',
        'password' => 'correct-password',
        'password_confirmation' => 'correct-password',
        'account_type' => 'owner',
        'personal_data_consent' => false,
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('personal_data_consent');
});

it('rejects registration with a duplicate phone number', function (): void {
    User::factory()->create(['phone' => '+79261234567']);

    $response = $this->postJson('/api/v1/auth/register', [
        'phone' => '+79261234567',
        'password' => 'correct-password',
        'password_confirmation' => 'correct-password',
        'account_type' => 'owner',
        'personal_data_consent' => true,
    ]);

    $response->assertUnprocessable()->assertJsonPath('message', function (string $message) {
        return str_contains($message, 'уже зарегистрирован');
    });
});

it('rejects registration with an elevated account type', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'phone' => '+79261234567',
        'password' => 'correct-password',
        'password_confirmation' => 'correct-password',
        'account_type' => 'admin',
        'personal_data_consent' => true,
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('account_type');
});
