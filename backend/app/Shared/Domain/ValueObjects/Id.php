<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

use Symfony\Component\Uid\Ulid;

/**
 * Сортируемый по времени идентификатор — первичный ключ сущностей, доступных через
 * публичный API. См. docs/database/00-conventions.md.
 */
final class Id
{
    private function __construct(private readonly string $value) {}

    public static function generate(): self
    {
        return new self((string) new Ulid);
    }

    public static function fromString(string $value): self
    {
        if (! Ulid::isValid($value)) {
            throw new \InvalidArgumentException("Invalid ULID: {$value}");
        }

        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
