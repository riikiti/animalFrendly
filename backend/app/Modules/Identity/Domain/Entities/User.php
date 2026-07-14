<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Entities;

use App\Modules\Identity\Domain\Enums\AccountType;
use App\Modules\Identity\Domain\Exceptions\PersonalDataConsentRequiredException;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class User
{
    private function __construct(
        private readonly Id $id,
        private readonly PhoneNumber $phone,
        private readonly string $passwordHash,
        private readonly AccountType $accountType,
        private readonly DateTimeImmutable $personalDataConsentAt,
    ) {}

    /**
     * Регистрация невозможна без явного согласия — 152-ФЗ, см. docs/plan/00-overview.md.
     */
    public static function register(
        Id $id,
        PhoneNumber $phone,
        string $passwordHash,
        AccountType $accountType,
        bool $personalDataConsentGiven,
    ): self {
        if (! $personalDataConsentGiven) {
            throw PersonalDataConsentRequiredException::create();
        }

        return new self($id, $phone, $passwordHash, $accountType, new DateTimeImmutable);
    }

    public static function reconstitute(
        Id $id,
        PhoneNumber $phone,
        string $passwordHash,
        AccountType $accountType,
        DateTimeImmutable $personalDataConsentAt,
    ): self {
        return new self($id, $phone, $passwordHash, $accountType, $personalDataConsentAt);
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function phone(): PhoneNumber
    {
        return $this->phone;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function accountType(): AccountType
    {
        return $this->accountType;
    }

    public function personalDataConsentAt(): DateTimeImmutable
    {
        return $this->personalDataConsentAt;
    }
}
