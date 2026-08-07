<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Contracts\SmsSenderInterface;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Отправку перехватываем и достаём из текста сам код — иначе его негде взять,
 * не зная соли хеша.
 */
function captureSmsCode(): Closure
{
    $sent = new stdClass;
    $sent->code = null;

    app()->instance(SmsSenderInterface::class, new class($sent) implements SmsSenderInterface
    {
        public function __construct(private readonly stdClass $sent) {}

        public function send(PhoneNumber $phone, string $text): void
        {
            preg_match('/(\d{4})/', $text, $matches);
            $this->sent->code = $matches[1] ?? null;
        }
    });

    return static fn (): ?string => $sent->code;
}

it('logs in with a code from sms', function (): void {
    $code = captureSmsCode();

    User::factory()->create(['phone' => '+79261234567']);

    $this->postJson('/api/v1/auth/phone-code', [
        'phone' => '+79261234567',
        'purpose' => 'login',
    ])->assertOk();

    $this->postJson('/api/v1/auth/phone-code/login', [
        'phone' => '+79261234567',
        'code' => $code(),
    ])->assertOk()->assertJsonStructure(['user' => ['id', 'phone'], 'token']);
});

it('refuses to reuse the same code twice', function (): void {
    $code = captureSmsCode();

    User::factory()->create(['phone' => '+79261234567']);

    $this->postJson('/api/v1/auth/phone-code', [
        'phone' => '+79261234567',
        'purpose' => 'login',
    ])->assertOk();

    $payload = ['phone' => '+79261234567', 'code' => $code()];

    $this->postJson('/api/v1/auth/phone-code/login', $payload)->assertOk();
    $this->postJson('/api/v1/auth/phone-code/login', $payload)->assertUnauthorized();
});

it('does not accept a login code for a password reset', function (): void {
    $code = captureSmsCode();

    User::factory()->create(['phone' => '+79261234567']);

    $this->postJson('/api/v1/auth/phone-code', [
        'phone' => '+79261234567',
        'purpose' => 'login',
    ])->assertOk();

    $this->postJson('/api/v1/auth/password/reset', [
        'phone' => '+79261234567',
        'code' => $code(),
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertUnauthorized();
});

it('resets the password by a code and lets the user in with it', function (): void {
    $code = captureSmsCode();

    User::factory()->create([
        'phone' => '+79261234567',
        'password_hash' => Hash::make('old-password'),
    ]);

    $this->postJson('/api/v1/auth/phone-code', [
        'phone' => '+79261234567',
        'purpose' => 'password_reset',
    ])->assertOk();

    $this->postJson('/api/v1/auth/password/reset', [
        'phone' => '+79261234567',
        'code' => $code(),
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertOk();

    $this->postJson('/api/v1/auth/login', [
        'phone' => '+79261234567',
        'password' => 'brand-new-password',
    ])->assertOk();
});

it('keeps the account name given at registration', function (): void {
    $this->postJson('/api/v1/auth/register', [
        'phone' => '+79261234599',
        'name' => 'Иван',
        'password' => 'correct-password',
        'password_confirmation' => 'correct-password',
        'personal_data_consent' => true,
    ])->assertCreated()->assertJsonPath('user.name', 'Иван');
});
