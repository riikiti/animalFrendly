<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

/**
 * Всегда целые минорные единицы (копейки) — никогда float. См. docs/database/00-conventions.md.
 */
final class Money
{
    private function __construct(
        public readonly int $minorUnits,
        public readonly string $currency,
    ) {
        if ($minorUnits < 0) {
            throw new \InvalidArgumentException('Money amount cannot be negative.');
        }

        if (strlen($currency) !== 3) {
            throw new \InvalidArgumentException('Currency must be a 3-letter ISO 4217 code.');
        }
    }

    public static function fromMinorUnits(int $minorUnits, string $currency = 'RUB'): self
    {
        return new self($minorUnits, strtoupper($currency));
    }

    public static function zero(string $currency = 'RUB'): self
    {
        return new self(0, strtoupper($currency));
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits - $other->minorUnits, $this->currency);
    }

    /**
     * @param  int  $basisPoints  например, 500 = 5.00%
     */
    public function percentage(int $basisPoints): self
    {
        return new self(intdiv($this->minorUnits * $basisPoints, 10000), $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->minorUnits === $other->minorUnits && $this->currency === $other->currency;
    }

    public function isZero(): bool
    {
        return $this->minorUnits === 0;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(
                "Currency mismatch: {$this->currency} vs {$other->currency}."
            );
        }
    }
}
