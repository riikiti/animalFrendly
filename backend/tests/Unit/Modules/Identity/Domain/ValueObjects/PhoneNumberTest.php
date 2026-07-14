<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Exceptions\InvalidPhoneNumberException;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;

it('normalizes a number typed with a leading 8', function (): void {
    expect(PhoneNumber::fromString('89261234567')->value())->toBe('+79261234567');
});

it('normalizes a number typed with a leading +7', function (): void {
    expect(PhoneNumber::fromString('+7 (926) 123-45-67')->value())->toBe('+79261234567');
});

it('normalizes a 10-digit number without a country code', function (): void {
    expect(PhoneNumber::fromString('9261234567')->value())->toBe('+79261234567');
});

it('rejects a number that is too short', function (): void {
    PhoneNumber::fromString('12345');
})->throws(InvalidPhoneNumberException::class);

it('compares phone numbers by normalized value', function (): void {
    expect(PhoneNumber::fromString('89261234567')->equals(PhoneNumber::fromString('+79261234567')))->toBeTrue();
});

it('masks the phone number for logs', function (): void {
    expect(PhoneNumber::fromString('+79261234567')->masked())->toBe('+792******67');
});
