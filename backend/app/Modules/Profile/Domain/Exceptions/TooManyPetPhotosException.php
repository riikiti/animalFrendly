<?php

declare(strict_types=1);

namespace App\Modules\Profile\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class TooManyPetPhotosException extends DomainException
{
    public static function create(int $limit): self
    {
        return new self("У анкеты не может быть больше {$limit} фото.");
    }
}
