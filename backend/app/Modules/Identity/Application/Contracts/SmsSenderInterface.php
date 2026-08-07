<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Contracts;

use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;

interface SmsSenderInterface
{
    public function send(PhoneNumber $phone, string $text): void;
}
