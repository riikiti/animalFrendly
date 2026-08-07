<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Entities;

use App\Modules\Identity\Domain\Enums\AccountType;
use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\Exceptions\PersonalDataConsentRequiredException;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class User
{
    private function __construct(
        private readonly Id $id,
        private readonly PhoneNumber $phone,
        private string $passwordHash,
        private readonly AccountType $accountType,
        private readonly DateTimeImmutable $personalDataConsentAt,
        private UserStatus $status,
        private ?string $address = null,
        private ?string $city = null,
        private ?float $latitude = null,
        private ?float $longitude = null,
        private ?string $name = null,
        private ?string $avatarUrl = null,
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
        ?string $name = null,
    ): self {
        if (! $personalDataConsentGiven) {
            throw PersonalDataConsentRequiredException::create();
        }

        return new self(
            $id,
            $phone,
            $passwordHash,
            $accountType,
            new DateTimeImmutable,
            UserStatus::Active,
            name: $name,
        );
    }

    public static function reconstitute(
        Id $id,
        PhoneNumber $phone,
        string $passwordHash,
        AccountType $accountType,
        DateTimeImmutable $personalDataConsentAt,
        UserStatus $status = UserStatus::Active,
        ?string $address = null,
        ?string $city = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $name = null,
        ?string $avatarUrl = null,
    ): self {
        return new self(
            $id,
            $phone,
            $passwordHash,
            $accountType,
            $personalDataConsentAt,
            $status,
            $address,
            $city,
            $latitude,
            $longitude,
            $name,
            $avatarUrl,
        );
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

    /**
     * Смена пароля — при восстановлении по коду из СМС и в настройках профиля.
     */
    public function changePassword(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function accountType(): AccountType
    {
        return $this->accountType;
    }

    public function personalDataConsentAt(): DateTimeImmutable
    {
        return $this->personalDataConsentAt;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function block(): void
    {
        $this->status = UserStatus::Blocked;
    }

    public function unblock(): void
    {
        $this->status = UserStatus::Active;
    }

    public function isBlocked(): bool
    {
        return $this->status === UserStatus::Blocked;
    }

    public function address(): ?string
    {
        return $this->address;
    }

    public function city(): ?string
    {
        return $this->city;
    }

    public function latitude(): ?float
    {
        return $this->latitude;
    }

    public function longitude(): ?float
    {
        return $this->longitude;
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Город и координаты приходят из геокодера вместе с адресом и всегда согласованы —
     * отдельных сеттеров на каждое поле нет, чтобы нельзя было выставить город без адреса.
     */
    public function setLocation(?string $address, ?string $city, ?float $latitude, ?float $longitude): void
    {
        $this->address = $address;
        $this->city = $city;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function avatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatar(?string $url): void
    {
        $this->avatarUrl = $url;
    }
}
