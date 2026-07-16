<?php

declare(strict_types=1);

namespace App\Modules\Profile\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class PetLimitExceededException extends DomainException
{
    public static function create(): self
    {
        return new self('Бесплатный тариф позволяет завести одну анкету питомца. Оформите подписку, чтобы добавить ещё.');
    }
}
