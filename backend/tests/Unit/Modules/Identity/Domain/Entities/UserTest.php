<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Enums\AccountType;
use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\Exceptions\PersonalDataConsentRequiredException;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use App\Shared\Domain\ValueObjects\Id;

it('registers a user when personal data consent is given', function (): void {
    $user = User::register(
        id: Id::generate(),
        phone: PhoneNumber::fromString('+79261234567'),
        passwordHash: 'hashed-password',
        accountType: AccountType::Owner,
        personalDataConsentGiven: true,
    );

    expect($user->accountType())->toBe(AccountType::Owner)
        ->and($user->personalDataConsentAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('refuses to register a user without personal data consent', function (): void {
    User::register(
        id: Id::generate(),
        phone: PhoneNumber::fromString('+79261234567'),
        passwordHash: 'hashed-password',
        accountType: AccountType::Owner,
        personalDataConsentGiven: false,
    );
})->throws(PersonalDataConsentRequiredException::class);

it('registers a user as active and can be blocked/unblocked', function (): void {
    $user = User::register(
        id: Id::generate(),
        phone: PhoneNumber::fromString('+79261234567'),
        passwordHash: 'hashed-password',
        accountType: AccountType::Owner,
        personalDataConsentGiven: true,
    );

    expect($user->status())->toBe(UserStatus::Active)
        ->and($user->isBlocked())->toBeFalse();

    $user->block();
    expect($user->isBlocked())->toBeTrue()
        ->and($user->status())->toBe(UserStatus::Blocked);

    $user->unblock();
    expect($user->isBlocked())->toBeFalse()
        ->and($user->status())->toBe(UserStatus::Active);
});

it('has no location by default and can have it set/cleared', function (): void {
    $user = User::register(
        id: Id::generate(),
        phone: PhoneNumber::fromString('+79261234567'),
        passwordHash: 'hashed-password',
        accountType: AccountType::Owner,
        personalDataConsentGiven: true,
    );

    expect($user->address())->toBeNull()
        ->and($user->city())->toBeNull()
        ->and($user->hasCoordinates())->toBeFalse();

    $user->setLocation('Москва, ул. Тверская, 1', 'Москва', 55.755, 37.617);

    expect($user->address())->toBe('Москва, ул. Тверская, 1')
        ->and($user->city())->toBe('Москва')
        ->and($user->latitude())->toBe(55.755)
        ->and($user->longitude())->toBe(37.617)
        ->and($user->hasCoordinates())->toBeTrue();

    $user->setLocation('Новый адрес без координат', null, null, null);

    expect($user->address())->toBe('Новый адрес без координат')
        ->and($user->city())->toBeNull()
        ->and($user->hasCoordinates())->toBeFalse();
});
