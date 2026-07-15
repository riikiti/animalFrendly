<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class ReviewAlreadySubmittedException extends DomainException
{
    public static function create(): self
    {
        return new self('Отзыв по этой сделке уже оставлен.');
    }
}
