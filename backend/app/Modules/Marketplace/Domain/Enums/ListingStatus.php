<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Enums;

enum ListingStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Archived = 'archived';
}
