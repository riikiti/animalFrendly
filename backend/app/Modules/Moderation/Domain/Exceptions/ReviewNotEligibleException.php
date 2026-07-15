<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class ReviewNotEligibleException extends DomainException
{
    public static function create(): self
    {
        return new self('Оставить отзыв можно только по своей завершённой сделке.');
    }
}
