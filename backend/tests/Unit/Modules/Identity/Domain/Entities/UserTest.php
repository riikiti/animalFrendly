<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Enums\AccountType;
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
