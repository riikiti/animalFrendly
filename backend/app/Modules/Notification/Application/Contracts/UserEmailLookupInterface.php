<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Contracts;

use App\Shared\Domain\ValueObjects\Id;

interface UserEmailLookupInterface
{
    public function emailFor(Id $userId): ?string;
}
