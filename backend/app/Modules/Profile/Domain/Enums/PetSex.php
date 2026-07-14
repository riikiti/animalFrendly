<?php

declare(strict_types=1);

namespace App\Modules\Profile\Domain\Enums;

enum PetSex: string
{
    case Male = 'male';
    case Female = 'female';
}
