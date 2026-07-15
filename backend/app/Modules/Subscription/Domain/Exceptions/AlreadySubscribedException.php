<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class AlreadySubscribedException extends DomainException
{
    public static function create(): self
    {
        return new self('У вас уже есть активная или ожидающая оплаты подписка.');
    }
}
