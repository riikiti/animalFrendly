<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObjects;

use App\Modules\Identity\Domain\Exceptions\InvalidPhoneNumberException;

/**
 * Нормализует и валидирует российские мобильные номера к виду +7XXXXXXXXXX.
 */
final class PhoneNumber
{
    private const PATTERN = '/^\+7\d{10}$/';

    private function __construct(private readonly string $value) {}

    public static function fromString(string $raw): self
    {
        $normalized = self::normalize($raw);

        if (! preg_match(self::PATTERN, $normalized)) {
            throw InvalidPhoneNumberException::forValue($raw);
        }

        return new self($normalized);
    }

    private static function normalize(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';

        if (strlen($digits) === 11 && ($digits[0] === '8' || $digits[0] === '7')) {
            $digits = '7'.substr($digits, 1);
        } elseif (strlen($digits) === 10) {
            $digits = '7'.$digits;
        }

        return '+'.$digits;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Частично замаскирован для логов — см. docs/plan/13-logging.md.
     */
    public function masked(): string
    {
        return substr($this->value, 0, 4).str_repeat('*', strlen($this->value) - 6).substr($this->value, -2);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
