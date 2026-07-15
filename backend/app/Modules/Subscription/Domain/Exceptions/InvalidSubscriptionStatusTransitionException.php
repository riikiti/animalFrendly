<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class InvalidSubscriptionStatusTransitionException extends DomainException
{
    public static function create(): self
    {
        return new self('Недопустимый переход статуса подписки.');
    }
}
