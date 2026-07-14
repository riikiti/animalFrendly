<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

/**
 * Регистрация без явного согласия нарушает 152-ФЗ — см. docs/plan/00-overview.md.
 */
final class PersonalDataConsentRequiredException extends DomainException
{
    public static function create(): self
    {
        return new self('Регистрация невозможна без согласия на обработку персональных данных.');
    }
}
