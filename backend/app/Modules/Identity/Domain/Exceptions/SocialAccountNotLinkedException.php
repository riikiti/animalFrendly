<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use DomainException;

final class SocialAccountNotLinkedException extends DomainException
{
    public static function create(): self
    {
        return new self(
            'Этот аккаунт ещё не привязан. Зарегистрируйтесь по номеру телефона, а затем привяжите его в профиле.',
        );
    }
}
