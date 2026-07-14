<?php

declare(strict_types=1);

namespace App\Modules\Profile\Domain\Enums;

enum PetPurpose: string
{
    case Social = 'social';
    case Breeding = 'breeding';
    // Заполняется соответствующими модулями, не через публичное создание анкеты —
    // см. docs/plan/07-flow-matching-shelter.md и docs/plan/08-flow-marketplace-escrow.md.
    case ForSale = 'for_sale';
    case Shelter = 'shelter';
}
